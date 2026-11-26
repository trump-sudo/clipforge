<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * FFmpeg / ImageMagick pipeline:
 *   1. ffprobe   -> duration and resolution
 *   2. ffmpeg    -> one frame at ~30% of the video
 *   3. magick    -> 640x360 center-cropped WebP thumbnail
 *   4. ffmpeg    -> short hover-preview: N segments from across the video, concatenated, no audio
 */
class VideoProcessor
{
    /**
     * @return array{duration: float, width: int, height: int, thumbnail_path: string, preview_path: string}
     */
    public function process(Video $video): array
    {
        $source = Storage::disk('local')->path($video->original_path);
        $meta = $this->probe($source);

        $dir = $video->assetDirectory();
        Storage::disk('public')->makeDirectory($dir);

        $thumbnail = "{$dir}/thumb.webp";
        $preview = "{$dir}/preview.mp4";

        $this->makeThumbnail($source, $meta['duration'], Storage::disk('public')->path($thumbnail));
        $this->makePreview($source, $meta['duration'], Storage::disk('public')->path($preview));

        return $meta + ['thumbnail_path' => $thumbnail, 'preview_path' => $preview];
    }

    /**
     * @return array{duration: float, width: int, height: int}
     */
    public function probe(string $source): array
    {
        $result = Process::timeout(60)->run([
            config('clipforge.ffprobe'), '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=width,height:format=duration',
            '-of', 'json',
            $source,
        ])->throw();

        $data = json_decode($result->output(), true);
        $stream = $data['streams'][0] ?? null;
        $duration = (float) ($data['format']['duration'] ?? 0);

        if (! $stream || $duration <= 0) {
            throw new RuntimeException('File has no readable video stream.');
        }

        return [
            'duration' => round($duration, 3),
            'width' => (int) $stream['width'],
            'height' => (int) $stream['height'],
        ];
    }

    private function makeThumbnail(string $source, float $duration, string $target): void
    {
        $tmpDir = $this->tmpDir();
        $frame = "{$tmpDir}/frame.jpg";

        try {
            Process::timeout(60)->run([
                config('clipforge.ffmpeg'), '-v', 'error', '-y',
                '-ss', $this->ts($duration * 0.3),
                '-i', $source,
                '-frames:v', '1', '-q:v', '2',
                $frame,
            ])->throw();

            ['width' => $w, 'height' => $h, 'quality' => $q] = config('clipforge.thumbnail');

            // "WxH^" fills the box, -extent crops the overflow: no letterboxing in the grid.
            Process::timeout(60)->run([
                config('clipforge.imagemagick'), $frame,
                '-resize', "{$w}x{$h}^",
                '-gravity', 'center',
                '-extent', "{$w}x{$h}",
                '-strip',
                '-quality', (string) $q,
                $target,
            ])->throw();
        } finally {
            File::deleteDirectory($tmpDir);
        }
    }

    private function makePreview(string $source, float $duration, string $target): void
    {
        ['segments' => $count, 'segment_seconds' => $len, 'width' => $width, 'crf' => $crf] = config('clipforge.preview');

        $starts = $this->segmentStarts($duration, $count, $len);
        $segLen = min($len, $duration);

        $inputs = [];
        $filters = [];
        $labels = '';
        foreach ($starts as $i => $start) {
            array_push($inputs, '-ss', $this->ts($start), '-t', $this->ts($segLen), '-i', $source);
            $filters[] = "[{$i}:v]scale={$width}:-2,setsar=1[v{$i}]";
            $labels .= "[v{$i}]";
        }
        $filters[] = $labels.'concat=n='.count($starts).':v=1:a=0[out]';

        Process::timeout(300)->run([
            config('clipforge.ffmpeg'), '-v', 'error', '-y',
            ...$inputs,
            '-filter_complex', implode(';', $filters),
            '-map', '[out]',
            '-an',
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', (string) $crf,
            '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart', // moov atom first: preview starts playing before it is fully downloaded
            $target,
        ])->throw();
    }

    /**
     * Evenly spread segment start points, skipping the very beginning and end
     * (intros and credits make poor previews). Short videos get a single segment from 0.
     *
     * @return list<float>
     */
    public function segmentStarts(float $duration, int $count, int $len): array
    {
        if ($duration < $count * $len * 2) {
            return [0.0];
        }

        $starts = [];
        for ($i = 1; $i <= $count; $i++) {
            $starts[] = round($duration * $i / ($count + 1) - $len / 2, 3);
        }

        return $starts;
    }

    private function ts(float $seconds): string
    {
        return number_format(max(0, $seconds), 3, '.', '');
    }

    private function tmpDir(): string
    {
        $dir = storage_path('app/tmp/'.uniqid('proc_', true));
        File::ensureDirectoryExists($dir);

        return $dir;
    }
}
