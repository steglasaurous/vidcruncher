<?php
namespace App\Message;

class MediaSplitMessage {
    public function __construct(private int $mediaFileId) {
    }

    public function getMediaFileId(): int {
        return $this->mediaFileId;
    }
}