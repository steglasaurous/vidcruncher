<?php

namespace App\MessageHandler;

use App\Entity\MediaFile;
use App\Entity\MediaType;
use App\Message\EncodeMessage;
use App\Message\MediaSplitMessage;
use App\Repository\MediaFileRepository;
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
    private string $splitOutputDir = 'fragments';

    public function __construct(
        private LoggerInterface $logger,
        private $vidCruncherVideosRoot,
        private MediaFileRepository $mediaFileRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private Filesystem $filesystem,
        private ParameterBagInterface $parameterBag,
        private string $vidCruncherCoordinatorBaseUrl
    )
    {}

    public function __invoke(MediaSplitMessage $mediaSplitMessage)
    {
        // NOTE: This handler assumes it's working locally with the files and should only be run on the coordinator.
        // Running this on a remote worker will fail.
        /** @var MediaFile $mediaFile */
        $mediaFile = $this->mediaFileRepository->find($mediaSplitMessage->getMediaFileId());

        $mediaFilePath = new \SplFileInfo($mediaFile->getMediaPath());
        $outputDir = sprintf('%s/%s', $this->vidCruncherVideosRoot, $this->splitOutputDir);
        if (!is_dir($outputDir)) {
            mkdir($outputDir);
        }
        $cmd = sprintf(
            'ffmpeg -y -i "%s" -map 0 -c copy -f segment -segment_time 60s "%s/%s-%%03d.%s"',
            $mediaFile->getMediaPath(),
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
            ->name(sprintf('%s-???.%s',$mediaFilePath->getBasename(), $mediaFilePath->getExtension()))
            ->in($outputDir)
            ->sortByName();

        $profile = $mediaFile->getMedia()->getProject()->getProfile();

        foreach ($finder as $file) {
            $newMediaFile = new MediaFile();
            $newMediaFile->setMediaType(MediaType::VideoFragment);
            $newMediaFile->setMedia($mediaFile->getMedia());
            $newMediaFile->setMediaPath($file->getRealPath());

            $this->entityManager->persist($newMediaFile);
            $this->entityManager->flush();

            $this->messageBus->dispatch(
                new EncodeMessage(
                    $newMediaFile->getId(),
                    sprintf('%s/%s', $this->vidCruncherCoordinatorBaseUrl,
                        $this->filesystem->makePathRelative(
                            $newMediaFile->getMediaPath(),
                            $this->parameterBag->get('kernel.project_dir') . '/public'
                        )
                    ),
                    $profile->getPreset(),
                    $profile->getCrf()
                )
            );
        }
    }
}