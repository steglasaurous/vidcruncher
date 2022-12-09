<?php

namespace App\Enum;

enum MediaStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Done       = 'done';
    case Failed     = 'failed';
}
