<?php

namespace Tests\Unit;

use App\Services\VideoProcessor;
use PHPUnit\Framework\TestCase;

class SegmentStartsTest extends TestCase
{
    public function test_long_video_gets_evenly_spread_segments(): void
    {
        $starts = (new VideoProcessor)->segmentStarts(duration: 120, count: 3, len: 2);

        $this->assertSame([29.0, 59.0, 89.0], $starts);
    }

    public function test_short_video_gets_single_segment_from_start(): void
    {
        $this->assertSame([0.0], (new VideoProcessor)->segmentStarts(duration: 8, count: 3, len: 2));
    }
}
