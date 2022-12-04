<?php

namespace App\MessageHandler;

use App\Entity\Media;
use App\Entity\MediaFile;
use App\Entity\MediaStatus;
use App\Entity\MediaType;
use App\Entity\Project;
use App\Entity\ProjectStatus;
use App\Message\AssembleMessage;
use App\Message\EncodeMessage;
use App\Message\MediaSplitMessage;
use App\Repository\MediaFileRepository;
use App\Repository\MediaRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use FFMpeg\FFMpeg;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class AssembleMessageHandler
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private EntityManagerInterface $entityManager,
        private FFMpeg $FFMpeg,
        private string $vidCruncherVideosRoot,
        private string $vidCruncherVideoFragmentsPath,
        private LoggerInterface $logger
    )
    {
    }

    public function __invoke(AssembleMessage $assembleMessage) {
        $this->logger->debug(sprintf('Assembling project %s', $assembleMessage->getProjectId()));

        /** @var Project $project */
        $project = $this->projectRepository->find($assembleMessage->getProjectId());
        $project->setStatus(ProjectStatus::Assembling);

        $this->entityManager->flush();

        $medias = $project->getMedia();
        $outputFiles = [];

        /** @var Media $media */
        foreach ($medias as $media)
        {
            /** @var MediaFile $mediaFile */
            $mediaFile = $media->getMediaFiles()->filter(function(MediaFile $file) {
                return $file->getMediaType() === MediaType::OutputVideo;
            })->first();

            $outputFiles[] = sprintf('%s/%s/%s', $this->vidCruncherVideosRoot, $this->vidCruncherVideoFragmentsPath, $mediaFile->getMediaPath());
        }

        // Sort based on filename so we get the correct order.
        sort($outputFiles);

        $video = $this->FFMpeg->open($outputFiles[0]);
        $video
            ->concat($outputFiles)
            ->saveFromSameCodecs(sprintf('%s/%s',  $this->vidCruncherVideosRoot, $project->getProfile()->getOutputPath()));

        $project->setStatus(ProjectStatus::Done);

        $this->entityManager->flush();
    }
}