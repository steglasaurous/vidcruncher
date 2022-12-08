<?php

namespace App\Message;

class MediaSplitMessage
{
    public function __construct(
        /**
         * Project this video's fragments should be added to.
         */
        private readonly int $projectId,
        /**
         * The path to the video file to split.
         */
        private readonly string $originalVideoPath
    ) {
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getOriginalVideoPath(): string
    {
        return $this->originalVideoPath;
    }
}
