<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Semantic media data shared by every consumer (Nuxt, AI, crawlers).
 * Never add UI state here: formatting and layout belong to the client.
 *
 * @mixin \App\Models\Media
 */
class MediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'author' => AuthorResource::make($this->whenLoaded('author')),
            'duration_seconds' => $this->duration_seconds,
            'published_at' => $this->published_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
            'files' => MediaFileResource::collection($this->whenLoaded('files')),
            'transcripts' => MediaTranscriptResource::collection($this->whenLoaded('transcripts')),
            'links' => [
                'self' => route('api.v1.media.show', $this->resource),
            ],
        ];
    }
}
