<?php

namespace App\MessageHandler;

use App\Enum\MediaStatus;
use App\Message\EncodeMessage;
use App\Service\FFMpeg\Format\AV1Format;
use FFMpeg\Exception\RuntimeException;
use FFMpeg\FFMpeg;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
class EncodeMessageHandler
{
    private string $hostname;

    public function __construct(
        private FFMpeg $FFMpeg,
        private LoggerInterface $logger,
        private ParameterBagInterface $parameterBag,
        private Filesystem $filesystem,
        private HttpClientInterface $coordinatorClient,
        private string $vidCruncherWorkerName
    ) {
        // Just using hostname for now, but wonder if there's a better way?
        if (empty($this->vidCruncherWorkerName)) {
            $this->vidCruncherWorkerName = gethostname();
        }
    }

    public function __invoke(EncodeMessage $encodeMessage)
    {
        // We assume AV1 for the codec for the time being.  If I wanna expand later, I should be able to do
        // that here.
        $outputDir  = $this->parameterBag->get('kernel.project_dir').'/var/videos';
        $outputFile = sprintf('%s/%s', $outputDir, $encodeMessage->getMediaFileName());

        $this->filesystem->mkdir($outputDir);

        $format = new AV1Format();
        $format->setKiloBitrate(0);
        $format->setCrf($encodeMessage->getCrf());
        $format->setPreset($encodeMessage->getPreset());
        $format->setAudioCodec('copy');

        $encode = $this->FFMpeg->openAdvanced([$encodeMessage->getMediaFileUrl()]);
        $encode->map(['0'], $format, $outputFile);

        // Push to the API for this media file that it's being processed.
        $this->updateMediaStatus($encodeMessage->getMediaId(), MediaStatus::Processing, new \DateTime());

        try {
            $encode->save();
        } catch (RuntimeException $e) {
            $this->logger->critical("ffmpeg failed: " . $e->getMessage());

            $this->updateMediaStatus($encodeMessage->getMediaId(), MediaStatus::Failed, null, new \DateTime());

            // Clean up - remove output file since it may or may not have been completed.
            $this->filesystem->remove($outputFile);

            throw $e;
        }

        $formFields = [
            'mediaType' => 'output_video',
            'media'     => sprintf('/api/media/%s', $encodeMessage->getMediaId()),
            'file'      => DataPart::fromPath($outputFile),
        ];

        $formData = new FormDataPart($formFields);
        $this->coordinatorClient->request('POST', '/api/media_files', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body'    => $formData->bodyToIterable(),
        ]);

        $this->logger->debug('Uploaded output file.');
        // Update the original media file that it's been completed.

        $this->updateMediaStatus($encodeMessage->getMediaId(), MediaStatus::Done, null, new \DateTime());

        // Delete the output file since it was uploaded successfully.
        $this->filesystem->remove($outputFile);
    }

    private function updateMediaStatus(int $mediaId, MediaStatus $status, \DateTime $start = null, \DateTime $completed = null)
    {
        $data = [
            'status'     => $status,
            'workerName' => $this->vidCruncherWorkerName,
        ];

        if ($start !== null) {
            $data['start'] = $start->format(\DateTime::RFC3339);
        }
        if ($completed !== null) {
            $data['completed'] = $completed->format(\DateTime::RFC3339);
        }

        $updateResponse = $this->coordinatorClient->request('PUT', sprintf('/api/media/%s', $mediaId), [
            'json' => $data,
        ]);

        // Just getting this to throw an exception if there's a problem.
        $updateResponse->getContent(true);
    }
}
