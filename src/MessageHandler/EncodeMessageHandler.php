<?php

namespace App\MessageHandler;
use App\Message\EncodeMessage;
use App\Service\FFMpeg\Format\AV1Format;
use FFMpeg\FFMpeg;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
class EncodeMessageHandler {
    private string $hostname;

    public function __construct(
        private FFMpeg $FFMpeg,
        private LoggerInterface $logger,
        private ParameterBagInterface $parameterBag,
        private Filesystem $filesystem,
        private HttpClientInterface $coordinatorClient,
    ) {
        // Just using hostname for now, but wonder if there's a better way?
        $this->hostname = gethostname();
    }

    public function __invoke(EncodeMessage $encodeMessage) {
        // We assume AV1 for the codec for the time being.  If I wanna expand later, I should be able to do
        // that here.
        $outputDir = $this->parameterBag->get('kernel.project_dir') . '/var/videos';
        $outputFile = sprintf('%s/%s', $outputDir, $encodeMessage->getMediaFileName());

        $this->filesystem->mkdir($outputDir);

        $format = new AV1Format();
        $format->setKiloBitrate(0);
        $format->setCrf($encodeMessage->getCrf());
        $format->setPreset($encodeMessage->getPreset());
        $format->setAudioCodec('copy');

        // Download the file locally (using ffmpeg to stream it incrementally seems to fail consistently)
        $response = $this->coordinatorClient->request('GET', $encodeMessage->getMediaFileUrl());

        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 399) {
            throw new \Exception('Failed to retrieve the original file: ' . $response->getStatusCode());
        }

        $originalFile = sprintf('%s/original-%s' , $outputDir, $encodeMessage->getMediaFileName());
        $fp = fopen($originalFile, 'w');
        foreach ($this->coordinatorClient->stream($response) as $chunk) {
            fwrite($fp, $chunk->getContent());
        }

        fclose($fp);

        $encode = $this->FFMpeg->openAdvanced([$originalFile]);
        $encode->map(['0'],$format,$outputFile);

        // Push to the API for this media file that it's being processed.
        $start = new \DateTime();

        $updateResponse = $this->coordinatorClient->request('PUT', sprintf('/api/media/%s',$encodeMessage->getMediaId()), [
            'json' => [
                'start' => $start->format(\DateTime::RFC3339),
                'status' => 'processing',
                'workerName' => $this->hostname
            ]
        ]);

        // Just getting this to throw an exception if there's a problem.
        $updateResponse->getContent(true);

        $encode->save();

        // FIXME: Next step: Implement file uploader in API so the result can be sent back to the coordinator.
        $formFields = [
            'mediaType' => 'output_video',
            'media' => sprintf('/api/media/%s', $encodeMessage->getMediaId()),
            'file' => DataPart::fromPath($outputFile)
        ];

        $formData = new FormDataPart($formFields);
        $this->coordinatorClient->request('POST', '/api/media_files', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable()
        ]);

        $this->logger->info('Uploaded output file.');
        // Update the original media file that it's been completed.

        $completed = new \DateTime();
        $updateResponse = $this->coordinatorClient->request('PUT', sprintf('/api/media/%s',$encodeMessage->getMediaId()), [
            'json' => [
                'completed' => $completed->format(\DateTime::RFC3339),
                'status' => 'done'
            ]
        ]);
        // Delete the output file since it was uploaded successfully.
        $this->filesystem->remove($outputFile);
        $this->filesystem->remove($originalFile);

        $this->logger->info('Deleted files');
    }
}