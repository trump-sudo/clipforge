<?php

namespace Database\Factories;

use App\Enums\VideoStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Video>
 */
class VideoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'status' => VideoStatus::Pending,
            'original_path' => 'uploads/'.fake()->uuid().'.mp4',
            'original_name' => 'clip.mp4',
            'size_bytes' => fake()->numberBetween(1_000_000, 50_000_000),
        ];
    }

    public function ready(): static
    {
        return $this->state(fn () => [
            'status' => VideoStatus::Ready,
            'duration' => 42.5,
            'width' => 1280,
            'height' => 720,
            'thumbnail_path' => 'videos/1/thumb.webp',
            'preview_path' => 'videos/1/preview.mp4',
            'processed_at' => now(),
        ]);
    }
}
