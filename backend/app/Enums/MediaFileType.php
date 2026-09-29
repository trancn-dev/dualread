<?php

namespace App\Enums;

enum MediaFileType: string
{
    case Original = 'original';
    case Thumbnail = 'thumbnail';
    case Preview = 'preview';
}
