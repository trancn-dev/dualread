<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\MediaTranscript
 */
class MediaTranscriptResource extends JsonResource
{
    /**
     * The list endpoint loads transcripts without their content,
     * so "content" is only present on the detail endpoint.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'language' => $this->language,
            'source' => $this->source,
            'content' => $this->whenHas('content'),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
