<?php

namespace App\MessageHandler;

use App\Entity\Media;
use App\Entity\MediaFile;
use App\Enum\MediaStatus;
use App\Enum\MediaType;
use App\Entity\Project;
use App\Enum\ProjectStatus;
use App\Message\EncodeMessage;
use App\Message\MediaSplitMessage;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class MediaSplitMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private readonly string $vidCruncherVideosRoot,
        private readonly string $vidCruncherVideoFragmentsPath,
        private ProjectRepository $projectRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private Filesystem $filesystem,
        private ParameterBagInterface $parameterBag,
        private string $vidCruncherCoordinatorBaseUrl
    ) {
    }

    public function __invoke(MediaSplitMessage $mediaSplitMessage)
    {
        /** @var Project $project */
        $project = $this->projectRepository->find($mediaSplitMessage->getProjectId());
        $profile = $project->getProfile();

        $project->setStatus(ProjectStatus::Splitting);
        $this->entityManager->flush();

        // NOTE: This handler assumes it's working locally with the files and should only be run on the coordinator.
        // Running this on a remote worker will fail.
        $mediaFilePath = new \SplFileInfo($mediaSplitMessage->getOriginalVideoPath());

        $outputDir = sprintf('%s/%s', $this->vidCruncherVideosRoot, $this->vidCruncherVideoFragmentsPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir);
        }

        // -map 0:v and -map 0:a will copy video and audio streams, and discard others. (ex: gopro recordings include streams of metadata of some sort - not needed for final product)
        $cmd = sprintf(
            'ffmpeg -y -i "%s" -map 0:v -map 0:a -c copy -write_tmcd 0 -f segment -segment_time 60s "%s/%s-%%03d.%s"',
            $mediaFilePath->getRealPath(),
            $outputDir,
            $mediaFilePath->getBasename(),
            $mediaFilePath->getExtension()
        );

        $this->logger->debug($cmd);

        // FIXME: Do some error handling here if the split process fails.
        exec($cmd);

        $finder = new Finder();
        $finder
            ->files()
            ->name(sprintf('%s-???.%s', $mediaFilePath->getBasename(), $mediaFilePath->getExtension()))
            ->in($outputDir)
            ->sortByName()
        ;

        foreach ($finder as $file) {
            $newMedia = new Media();
            $newMedia->setStatus(MediaStatus::Pending);
            $newMedia->setProject($project);

            $newMediaFile = new MediaFile();
            $newMediaFile->setMediaType(MediaType::VideoFragment);
            $newMediaFile->setMedia($newMedia);
            $newMediaFile->setMediaPath($file->getRealPath());

            $this->entityManager->persist($newMedia);
            $this->entityManager->persist($newMediaFile);
            $this->entityManager->flush();

            $mediaFileUrl = sprintf(
                '%s/%s',
                $this->vidCruncherCoordinatorBaseUrl,
                str_replace(
                    ' ',
                    '%20',
                    $this->filesystem->makePathRelative(
                        $newMediaFile->getMediaPath(),
                        $this->parameterBag->get('kernel.project_dir').'/public'
                    )
                )
            );
            $mediaFileUrl = substr($mediaFileUrl, 0, \strlen($mediaFileUrl) - 1);

            $this->messageBus->dispatch(
                new EncodeMessage(
                    $newMedia->getId(),
                    $newMediaFile->getId(),
                    $mediaFileUrl,
                    $file->getBasename(),
                    $profile->getPreset(),
                    $profile->getCrf()
                )
            );
        }

        // Set the project to processing.
        $project->setStatus(ProjectStatus::Processing);
        $this->entityManager->flush();
    }
}
