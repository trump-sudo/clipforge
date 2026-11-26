<?php

namespace App\Jobs;

use App\Enums\VideoStatus;
use App\Models\Video;
use App\Services\TelegramNotifier;
use App\Services\VideoProcessor;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class ProcessVideo implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    public int $backoff = 30;

    public function __construct(public Video $video)
    {
        $this->onQueue('media');
    }

    public function uniqueId(): string
    {
        return (string) $this->video->id;
    }

    public function handle(VideoProcessor $processor, TelegramNotifier $telegram): void
    {
        $this->video->update(['status' => VideoStatus::Processing, 'error' => null]);

        $result = $processor->process($this->video);

        $this->video->update($result + [
            'status' => VideoStatus::Ready,
            'processed_at' => now(),
        ]);

        $telegram->send(sprintf(
            "✅ Video ready: %s\n%s · %dx%d\n%s",
            $this->video->title,
            $this->video->durationLabel(),
            $this->video->width,
            $this->video->height,
            route('videos.show', $this->video),
        ));
    }

    public function failed(?Throwable $e): void
    {
        $this->video->update([
            'status' => VideoStatus::Failed,
            'error' => Str::limit($e?->getMessage() ?? 'Unknown error', 1000),
        ]);

        app(TelegramNotifier::class)->send("❌ Processing failed: {$this->video->title}");
    }
}
