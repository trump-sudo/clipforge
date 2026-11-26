<?php

namespace Tests\Feature;

use App\Enums\VideoStatus;
use App\Jobs\ProcessVideo;
use App\Models\Video;
use App\Services\ViewCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
    }

    public function test_upload_stores_file_and_queues_processing(): void
    {
        $response = $this->post(route('videos.store'), [
            'title' => 'My first clip',
            'file' => UploadedFile::fake()->create('clip.mp4', 2048, 'video/mp4'),
        ]);

        $video = Video::sole();

        $response->assertRedirect(route('videos.show', $video));
        $this->assertSame(VideoStatus::Pending, $video->status);
        $this->assertStringStartsWith('my-first-clip-', $video->slug);
        Storage::disk('local')->assertExists($video->original_path);

        Queue::assertPushedOn('media', ProcessVideo::class, fn (ProcessVideo $job) => $job->video->is($video));
    }

    public function test_ajax_upload_returns_json_with_status_url(): void
    {
        $this->postJson(route('videos.store'), [
            'title' => 'Ajax clip',
            'file' => UploadedFile::fake()->create('clip.webm', 512, 'video/webm'),
        ])
            ->assertCreated()
            ->assertJsonStructure(['slug', 'url', 'status_url']);
    }

    public function test_non_video_files_are_rejected(): void
    {
        $this->postJson(route('videos.store'), [
            'title' => 'Not a video',
            'file' => UploadedFile::fake()->create('evil.php', 10, 'text/x-php'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('videos', 0);
        Queue::assertNothingPushed();
    }

    public function test_status_endpoint_reports_processing_state(): void
    {
        $video = Video::factory()->create(['status' => VideoStatus::Processing]);

        $this->getJson(route('videos.status', $video))
            ->assertOk()
            ->assertJson(['status' => 'processing', 'final' => false]);
    }

    public function test_viewing_a_ready_video_counts_a_view(): void
    {
        $video = Video::factory()->ready()->create();

        $this->mock(ViewCounter::class)
            ->shouldReceive('hit')->once()
            ->withArgs(fn (Video $v) => $v->is($video));

        $this->get(route('videos.show', $video))->assertOk()->assertSee($video->title);
    }

    public function test_pending_videos_are_not_streamed(): void
    {
        $video = Video::factory()->create();

        $this->get(route('videos.stream', $video))->assertNotFound();
    }
}
