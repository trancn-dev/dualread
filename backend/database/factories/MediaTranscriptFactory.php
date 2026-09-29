<?php

namespace Database\Factories;

use App\Enums\TranscriptSource;
use App\Models\Media;
use App\Models\MediaTranscript;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaTranscript>
 */
class MediaTranscriptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'media_id' => Media::factory(),
            'language' => 'en',
            'content' => fake()->paragraphs(3, true),
            'source' => TranscriptSource::Manual,
        ];
    }
}
