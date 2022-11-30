<?php

namespace App\MessageHandler;
use App\Message\EncodeMessage;
use App\Service\FFMpeg\Format\AV1Format;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class EncodeMessageHandler {
    public function __construct(
        private FFMpeg $FFMpeg,
        private LoggerInterface $logger,
        private ParameterBagInterface $parameterBag,
        private Filesystem $filesystem
    ) {
    }

    public function __invoke(EncodeMessage $encodeMessage) {
        // We assume AV1 for the codec for the time being.  If I wanna expand later, I should be able to do
        // that here.
        $outputDir = $this->parameterBag->get('kernel.project_dir') . '/var/videos';
        $this->filesystem->mkdir($outputDir);

        $format = new AV1Format();
        $format->setKiloBitrate(0);
        $format->setCrf($encodeMessage->getCrf());
        $format->setPreset($encodeMessage->getPreset());
        $format->setAudioCodec('copy');

        $encode = $this->FFMpeg->openAdvanced([$encodeMessage->getMediaFileUrl()]);
        $encode->map(['0'],$format,sprintf('%s/output.mkv', $outputDir));

        $encode->save();

        // FIXME: Next step: Implement file uploader in API so the result can be sent back to the coordinator.
    }
}