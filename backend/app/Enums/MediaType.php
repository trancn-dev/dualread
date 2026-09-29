<?php

namespace App\Enums;

enum MediaType: string
{
    case Video = 'video';
    case Audio = 'audio';
    case Image = 'image';

    /**
     * Whether media of this type has a playback duration.
     */
    public function hasDuration(): bool
    {
        return $this !== self::Image;
    }
}
