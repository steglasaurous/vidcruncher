<?php

namespace App\MessageHandler;

use App\Entity\Media;
use App\Entity\MediaFile;
use App\Enum\MediaType;
use App\Entity\Project;
use App\Enum\ProjectStatus;
use App\Message\AssembleMessage;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AssembleMessageHandler
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $vidCruncherVideosRoot,
        private readonly string $vidCruncherVideoFragmentsPath,
        private readonly LoggerInterface $logger,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function __invoke(AssembleMessage $assembleMessage)
    {
        $this->logger->debug(sprintf('Assembling project %s', $assembleMessage->getProjectId()));

        /** @var Project $project */
        $project = $this->projectRepository->find($assembleMessage->getProjectId());
        $project->setStatus(ProjectStatus::Assembling);

        $this->entityManager->flush();

        $medias      = $project->getMedia();
        $outputFiles = [];

        /** @var Media $media */
        foreach ($medias as $media) {
            /** @var MediaFile $mediaFile */
            $mediaFile = $media->getMediaFiles()->filter(function (MediaFile $file) {
                return $file->getMediaType() === MediaType::OutputVideo;
            })->first();

            $outputFiles[] = sprintf('%s/%s/%s', $this->vidCruncherVideosRoot, $this->vidCruncherVideoFragmentsPath, $mediaFile->getMediaPath());
        }

        // Sort based on filename, so we get the correct order.
        sort($outputFiles);
        $finalOutputFile   = sprintf('%s/%s/%s', $this->vidCruncherVideosRoot, $project->getProfile()->getOutputPath(), $project->getOutputFilename());
        $textFilePath      = sprintf('%s.txt', $finalOutputFile);
        $outputFileContent = '';
        foreach ($outputFiles as $outputFile) {
            $outputFileContent .= sprintf("file '%s'\n", $outputFile);
        }

        $this->filesystem->dumpFile($textFilePath, $outputFileContent);

        // the FFMpeg PHP side doesn't include copying all audio tracks, so using the command directly instead.
        $cmd = sprintf('ffmpeg -hide_banner -y -f concat -safe 0 -i "%s" -c copy -map 0 "%s"', $textFilePath, $finalOutputFile);

        exec($cmd, $cmdOutput, $cmdResult);
        if ($cmdResult > 0) {
            // FFMPEG blew up somewhere. Throw an exception about it.
            $this->logger->error('FFMpeg returned non-zero result while assembling.');
            $this->logger->debug(implode("\n", $cmdOutput));

            throw new \Exception('FFMPEG returned non-zero result.');
        }

        $this->filesystem->remove($textFilePath);

        $project->setStatus(ProjectStatus::Done);

        $this->entityManager->flush();
    }
}
