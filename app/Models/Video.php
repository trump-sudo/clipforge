<?php

namespace App\Models;

use App\Enums\VideoStatus;
use App\Services\VideoCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'status', 'original_path', 'original_name', 'size_bytes',
        'duration', 'width', 'height', 'thumbnail_path', 'preview_path', 'error', 'processed_at',
    ];

    protected $attributes = [
        'status' => 'pending',
        'views' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => VideoStatus::class,
            'duration' => 'float',
            'width' => 'integer',
            'height' => 'integer',
            'size_bytes' => 'integer',
            'views' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Video $video) {
            $video->slug ??= Str::slug(Str::limit($video->title, 60, '')).'-'.Str::lower(Str::random(6));
        });

        // Any change to a video may change the public catalog: bump the cache version.
        static::saved(fn () => app(VideoCatalog::class)->flush());
        static::deleted(fn () => app(VideoCatalog::class)->flush());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeReady(Builder $query): void
    {
        $query->where('status', VideoStatus::Ready);
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : null;
    }

    public function previewUrl(): ?string
    {
        return $this->preview_path ? Storage::disk('public')->url($this->preview_path) : null;
    }

    public function durationLabel(): string
    {
        $seconds = (int) round($this->duration ?? 0);

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    public function assetDirectory(): string
    {
        return "videos/{$this->id}";
    }
}
