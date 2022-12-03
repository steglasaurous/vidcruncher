<?php
namespace App\Message;

// We make the assumption we're encoding to AV1 all the time.
// If I wanna expand on this later, I can include details about other codecs here, but
// to do that right, it'll get messy. (other codecs vary wildly in options, etc)
class EncodeMessage {
    public function __construct(
        private readonly int    $mediaId,
        private readonly int    $mediaFileId,
        private readonly string $mediaFileUrl,
        private readonly string $mediaFileName,
        private readonly int    $preset,
        private readonly int    $crf
    ) {}


    public function getMediaId(): int
    {
        return $this->mediaId;
    }

    public function getMediaFileId(): int
    {
        return $this->mediaFileId;
    }

    public function getMediaFileUrl(): string
    {
        return $this->mediaFileUrl;
    }

    public function getMediaFileName(): string
    {
        return $this->mediaFileName;
    }

    public function getPreset(): int
    {
        return $this->preset;
    }

    public function getCrf(): int
    {
        return $this->crf;
    }
}