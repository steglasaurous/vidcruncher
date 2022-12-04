<?php
namespace App\Message;

class AssembleMessage {
    public function __construct(
        /**
         * Project this video's fragments should be added to
         */
        private readonly int $projectId,
    ) {
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }
}