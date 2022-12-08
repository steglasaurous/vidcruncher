<?php

namespace App\Service\FFMpeg\Format;

use FFMpeg\Exception\InvalidArgumentException;
use FFMpeg\Format\Video\DefaultVideo;

class AV1Format extends DefaultVideo
{
    private $preset = 8;

    private $crf = 40;

    public function __construct()
    {
        $this->setVideoCodec('libsvtav1');
    }

    public function getAvailableAudioCodecs(): array
    {
        return ['copy'];
    }

    public function supportBFrames(): bool
    {
        return true;
    }

    public function getAvailableVideoCodecs(): array
    {
        return ['libsvtav1'];
    }

    public function setPreset(int $preset): self
    {
        if ($preset > 10 || $preset < 1) {
            throw new InvalidArgumentException('Preset must be between 1-10');
        }

        $this->preset = $preset;

        return $this;
    }

    public function setCrf(int $crf): self
    {
        $this->crf = $crf;

        return $this;
    }

    public function getAdditionalParameters()
    {
        $additionalParameters   = $this->additionalParamaters;
        $additionalParameters[] = '-preset';
        $additionalParameters[] = $this->preset;
        $additionalParameters[] = '-crf';
        $additionalParameters[] = $this->crf;

        return $additionalParameters;
    }
}
