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
use App\Repository\MediaFileRepository;
use App\Repository\MediaRepository;
use App\Repository\ProfileRepository;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityManagerInterface;
use FFMpeg\Exception\RuntimeException;
use FFMpeg\FFProbe;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Messenger\MessageBusInterface;

class InputPathScanner {
    public function __construct(
        private string $vidCruncherVideosRoot,
        private int $vidCruncherSplitThresholdSeconds,
        private ProfileRepository $profileRepository,
        private ProjectRepository $projectRepository,
        private MediaFileRepository $mediaFileRepository,
        private EntityManagerInterface $entityManager,
        private FFProbe $ffProbe,
        private LoggerInterface $logger,
        private MessageBusInterface $messageBus
    )
    {
    }

    public function scanAll() {
        $profiles = $this->profileRepository->findAll();
        /** @var Profile $profile */
        foreach ($profiles as $profile) {
            $this->scanProfile($profile);
        }
    }

    public function scanProfile(Profile $profile) {

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

    public function processFile(SplFileInfo $file, Profile $profile) {
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

            $project = $this->projectRepository->createQueryBuilder('p')
                ->addCriteria($criteria)
                ->getQuery()
                ->execute();

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
            $project->setProfile($profile);
        }

        try {
            $duration = $this->ffProbe->format($file)->get('duration');
        } catch (RuntimeException $e) {
            $this->logger->warning(sprintf('Unable to read file %s: %s', $file->getFilename(), $e->getMessage()));

            return;
        }

        $mediaFile = $this->mediaFileRepository->findBy(['mediaPath' => $file->getRealPath()]);
        if ($mediaFile) {
            return;
        }

        $media = new Media();
        $media->setProfile($profile);
        $media->setProject($project);
        $media->setOriginFilename($file->getFilename());
        $media->setStatus(MediaStatus::Pending);

        $mediaFile = new MediaFile();
        $mediaFile->setMedia($media);
        $mediaFile->setMediaPath($file->getRealPath());
        if ($profile->isLiveRecordings()) {
            $mediaFile->setMediaType(MediaType::VideoFragment);
        } else {
            $mediaFile->setMediaType(MediaType::OriginalVideo);
        }

        $this->entityManager->persist($project);
        $this->entityManager->persist($media);
        $this->entityManager->persist($mediaFile);
        $this->entityManager->flush();

        if ($duration > $this->vidCruncherSplitThresholdSeconds) {
            $this->logger->debug('This needs splitting.');
            $this->messageBus->dispatch(new MediaSplitMessage($mediaFile->getId()));
        }

        $this->logger->debug(sprintf('Read file %s', $file->getFilename()));
    }
}