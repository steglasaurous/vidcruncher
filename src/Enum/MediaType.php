<?php

namespace App\Enum;

enum MediaType: string
{
    case OriginalVideo =  'original_video';
    case VideoFragment = 'video_fragment';
    case OutputVideo   = 'output_video';
}
