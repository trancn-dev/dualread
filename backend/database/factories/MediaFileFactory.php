<?php

namespace Database\Factories;

use App\Enums\MediaFileType;
use App\Models\Media;
use App\Models\MediaFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaFile>
 */
class MediaFileFactory extends Factory
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
            'type' => MediaFileType::Original,
            'disk' => 'public',
            'path' => 'media/'.fake()->uuid().'.mp4',
            'mime_type' => 'video/mp4',
            'size_bytes' => fake()->numberBetween(1_000_000, 500_000_000),
            'width' => 1920,
            'height' => 1080,
        ];
    }

    public function thumbnail(): static
    {
        return $this->state(fn () => [
            'type' => MediaFileType::Thumbnail,
            'path' => 'media/'.fake()->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(10_000, 500_000),
            'width' => 640,
            'height' => 360,
        ]);
    }
}
