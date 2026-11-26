<?php

namespace Tests\Feature;

use App\Enums\VideoStatus;
use App\Models\Video;
use App\Services\VideoCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_only_ready_videos(): void
    {
        $ready = Video::factory()->ready()->create();
        Video::factory()->create(['status' => VideoStatus::Processing]);
        Video::factory()->create(['status' => VideoStatus::Failed]);

        $page = app(VideoCatalog::class)->page();

        $this->assertSame(1, $page->total());
        $this->assertTrue($page->first()->is($ready));
    }

    public function test_second_request_is_served_from_cache(): void
    {
        Video::factory()->ready()->count(3)->create();
        $catalog = app(VideoCatalog::class);

        $catalog->page(); // warm

        DB::enableQueryLog();
        $catalog->page();

        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_cache_is_invalidated_when_a_video_becomes_ready(): void
    {
        $catalog = app(VideoCatalog::class);
        $video = Video::factory()->create(['status' => VideoStatus::Processing]);

        $this->assertSame(0, $catalog->page()->total());

        $video->update(['status' => VideoStatus::Ready]);

        $this->assertSame(1, $catalog->page()->total());
    }

    public function test_json_api_returns_catalog(): void
    {
        Video::factory()->ready()->count(2)->create();

        $this->getJson(route('api.videos.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure(['data' => ['*' => ['slug', 'title', 'duration', 'thumbnail_url', 'preview_url', 'url']]]);
    }
}
