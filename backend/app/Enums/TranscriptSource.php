<?php

namespace App\Enums;

enum TranscriptSource: string
{
    case Manual = 'manual';
    case Auto = 'auto';
    case Imported = 'imported';
}
