<?php

namespace App\Enum;

enum ProjectStatus: string
{
    case Pending          = 'pending';
    case Splitting        = 'splitting';
    case Processing       = 'processing';
    case ReadyForAssembly = 'ready_for_assembly';
    case Assembling       = 'assembling';
    case Done             = 'done';
    case Failed           = 'failed';
}
