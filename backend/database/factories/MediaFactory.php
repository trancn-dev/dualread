<?php

namespace Database\Factories;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(4), '.');

        return [
            'slug' => Str::slug($title),
            'type' => MediaType::Video,
            'title' => $title,
            'description' => fake()->paragraph(),
            'author_id' => User::factory(),
            'duration_seconds' => fake()->numberBetween(30, 1800),
            'published_at' => fake()->dateTimeBetween('-1 year', '-1 day'),
            'status' => MediaStatus::Published,
        ];
    }

    public function video(): static
    {
        return $this->state(fn () => ['type' => MediaType::Video]);
    }

    public function audio(): static
    {
        return $this->state(fn () => ['type' => MediaType::Audio]);
    }

    public function image(): static
    {
        return $this->state(fn () => [
            'type' => MediaType::Image,
            'duration_seconds' => null,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => MediaStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['published_at' => now()->addWeek()]);
    }
}
