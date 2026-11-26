<?php

namespace Tests\Feature;

use App\Enums\VideoStatus;
use App\Jobs\ProcessVideo;
use App\Models\Video;
use App\Services\TelegramNotifier;
use App\Services\VideoProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProcessVideoJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Http::fake();
    }

    private function fakeProbe(string $json): void
    {
        Process::fake([
            '*ffprobe*' => Process::result($json),
            '*' => Process::result(''),
        ]);
    }

    private function commandLine(PendingProcess $process): string
    {
        return is_array($process->command) ? implode(' ', $process->command) : (string) $process->command;
    }

    public function test_job_extracts_metadata_and_builds_assets(): void
    {
        $this->fakeProbe('{"streams":[{"width":1920,"height":1080}],"format":{"duration":"120.5"}}');
        $video = Video::factory()->create();

        ProcessVideo::dispatchSync($video);

        $video->refresh();
        $this->assertSame(VideoStatus::Ready, $video->status);
        $this->assertSame(120.5, $video->duration);
        $this->assertSame(1920, $video->width);
        $this->assertSame("videos/{$video->id}/thumb.webp", $video->thumbnail_path);
        $this->assertSame("videos/{$video->id}/preview.mp4", $video->preview_path);

        // Thumbnail is cropped to fill 640x360 (no letterbox)…
        Process::assertRan(fn (PendingProcess $p) => str_contains($this->commandLine($p), '640x360^'));
        // …and the preview concatenates 3 segments.
        Process::assertRan(fn (PendingProcess $p) => str_contains($this->commandLine($p), 'concat=n=3'));
    }

    public function test_invalid_file_raises_and_failed_handler_marks_video(): void
    {
        $this->fakeProbe('{"streams":[],"format":{}}');
        $video = Video::factory()->create();
        $job = new ProcessVideo($video);

        try {
            $job->handle(app(VideoProcessor::class), app(TelegramNotifier::class));
            $this->fail('Expected exception');
        } catch (RuntimeException $e) {
            $job->failed($e);
        }

        $this->assertSame(VideoStatus::Failed, $video->fresh()->status);
    }

    public function test_telegram_is_notified_when_configured(): void
    {
        config(['clipforge.telegram.bot_token' => 'TEST', 'clipforge.telegram.chat_id' => '42']);
        $this->fakeProbe('{"streams":[{"width":1280,"height":720}],"format":{"duration":"30"}}');

        ProcessVideo::dispatchSync(Video::factory()->create(['title' => 'Demo']));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.telegram.org/botTEST/sendMessage')
            && $request['chat_id'] === '42'
            && str_contains($request['text'], 'Demo'));
    }
}
