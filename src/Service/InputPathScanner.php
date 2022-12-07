<?php
namespace App\Service;

use App\Entity\Media;
use App\Entity\MediaFile;
use App\Entity\MediaStatus;
use App\Entity\MediaType;
use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\ProjectStatus;
use App\Message\MediaSplitMessage;
use App\Repository\ProfileRepository;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityManagerInterface;
use FFMpeg\Exception\RuntimeException;
use FFMpeg\FFProbe;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Messenger\MessageBusInterface;

class InputPathScanner {
    public function __construct(
        private readonly string                 $vidCruncherVideosRoot,
        private readonly int                    $vidCruncherSplitThresholdSeconds,
        private readonly string                 $vidCruncherVideoFragmentsPath,
        private readonly ProfileRepository      $profileRepository,
        private readonly ProjectRepository      $projectRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly FFProbe                $ffProbe,
        private readonly LoggerInterface        $logger,
        private readonly MessageBusInterface    $messageBus,
        private readonly Filesystem             $filesystem
    )
    {
    }

    public function scanAll(): void
    {
        $profiles = $this->profileRepository->findAll();
        /** @var Profile $profile */
        foreach ($profiles as $profile) {
            $this->scanProfile($profile);
        }
    }

    public function scanProfile(Profile $profile): void
    {

        $duration = 0;
        $inputPath = sprintf('%s/%s', $this->vidCruncherVideosRoot, $profile->getInputPath());
        if (!is_dir($inputPath)) {
            // FIXME: This sucks for unit testing.  Alternatives?
            $this->logger->debug($inputPath);
            mkdir($inputPath);
        }

        $finder = new Finder();
        $finder->files()->ignoreDotFiles(true)->in($inputPath);

        foreach ($finder as $file) {
            $this->processFile($file, $profile);
        }
    }

    public function processFile(SplFileInfo $file, Profile $profile): void
    {
        if (time() - $file->getMTime() < $profile->getProcessModifiedOlderThan()) {
            return;
        }

        if ($profile->isLiveRecordings()) {
            // Live recordings will arrive as split files as it's being recorded.
            // We assume if there's a project present that's not in the 'done' or 'failed' state,
            // that's the project to use as we go.

            $criteria = new Criteria();
            $criteria->where(new Comparison('status', Comparison::IN, [ProjectStatus::Pending, ProjectStatus::Processing, ProjectStatus::ReadyForAssembly]));
            $criteria->andWhere(new Comparison('profile', Comparison::EQ, $profile));

            $projectResult = $this->projectRepository->createQueryBuilder('p')
                ->addCriteria($criteria)
                ->getQuery()
                ->execute();
            if (count($projectResult) > 0) {
                $project = $projectResult[0];
            }

        } else {
            // For non-live profiles, if the project's created, that's enough and can ignore it moving forward.
            $project = $this->projectRepository->findOneBy(['originFilePath' => $file->getRealPath()]);
            if ($project) {
                return;
            }
        }

        if (!$project) {
            $project = new Project();
            $project->setStatus(ProjectStatus::Pending);
            $project->setOriginFilePath($file->getRealPath());
            $project->setOutputFilename($file->getFilename());
            $project->setProfile($profile);
        }

        try {
            $duration = $this->ffProbe->format($file)->get('duration');
        } catch (RuntimeException $e) {
            $this->logger->warning(sprintf('Unable to read file %s: %s', $file->getFilename(), $e->getMessage()));

            return;
        }
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        if ($duration > $this->vidCruncherSplitThresholdSeconds) {
            $this->logger->debug(sprintf('Queueing %s for file splitting', $file->getFilename()));
            $this->messageBus->dispatch(new MediaSplitMessage($project->getId(), $file->getRealPath()));

            // Let the split take care of the rest.
            return;
        }


        // The remainder of this handling covers live videos, OR videos that don't meet the duration threshold
        // Move the file to the 'fragments' path
        $newFilePath = sprintf('%s/%s/%s', $this->vidCruncherVideosRoot, $this->vidCruncherVideoFragmentsPath, $file->getFilename());

        $this->filesystem->rename($file->getRealPath(), $newFilePath);

        // We'll make the assumption that ALL media items reside in theh same path, so all it needs to know is the filename.
        $media = new Media();
        $media->setProject($project);
        $media->setStatus(MediaStatus::Pending);

        $mediaFile = new MediaFile();
        $mediaFile->setMedia($media);
        $mediaFile->setMediaPath($newFilePath);
        $mediaFile->setMediaType(MediaType::VideoFragment);

        $this->entityManager->persist($media);
        $this->entityManager->persist($mediaFile);

        // Update project to processing, since it's ready to do so.
        $project->setStatus(ProjectStatus::Processing);

        $this->entityManager->flush();

        $this->logger->debug(sprintf('Processed file %s', $file->getFilename()));
    }
}