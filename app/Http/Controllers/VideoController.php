<?php

namespace App\Http\Controllers;

use App\Enums\VideoStatus;
use App\Jobs\ProcessVideo;
use App\Models\Video;
use App\Services\VideoCatalog;
use App\Services\ViewCounter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VideoController extends Controller
{
    public function __construct(private readonly VideoCatalog $catalog) {}

    public function index(Request $request): View
    {
        return view('videos.index', [
            'videos' => $this->catalog->page((int) $request->query('page', 1)),
        ]);
    }

    public function create(): View
    {
        return view('videos.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'file' => [
                'required', 'file',
                'mimetypes:'.implode(',', config('clipforge.upload.mimetypes')),
                'max:'.config('clipforge.upload.max_kb'),
            ],
        ]);

        $file = $data['file'];
        $path = $file->storeAs('uploads', Str::uuid().'.'.$file->extension(), 'local');

        $video = Video::create([
            'title' => $data['title'],
            'original_path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'size_bytes' => $file->getSize(),
        ]);

        ProcessVideo::dispatch($video);

        if ($request->expectsJson()) {
            return response()->json([
                'slug' => $video->slug,
                'url' => route('videos.show', $video),
                'status_url' => route('videos.status', $video),
            ], 201);
        }

        return redirect()->route('videos.show', $video);
    }

    public function show(Video $video, ViewCounter $views): View
    {
        if ($video->status === VideoStatus::Ready) {
            $views->hit($video);
        }

        return view('videos.show', ['video' => $video]);
    }

    public function status(Video $video): JsonResponse
    {
        return response()->json([
            'status' => $video->status->value,
            'final' => $video->status->isFinal(),
            'thumbnail_url' => $video->thumbnailUrl(),
            'preview_url' => $video->previewUrl(),
            'error' => $video->status === VideoStatus::Failed ? 'Processing failed' : null,
        ]);
    }

    /**
     * Streams the original. BinaryFileResponse handles HTTP Range requests, so seeking works.
     * In production this would be an nginx X-Accel-Redirect or a signed CDN URL.
     */
    public function stream(Video $video): BinaryFileResponse
    {
        abort_unless($video->status === VideoStatus::Ready, 404);

        return response()->file(Storage::disk('local')->path($video->original_path));
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $page = $this->catalog->page((int) $request->query('page', 1));

        return response()->json([
            'data' => $page->getCollection()->map(fn (Video $v) => [
                'slug' => $v->slug,
                'title' => $v->title,
                'duration' => $v->duration,
                'resolution' => "{$v->width}x{$v->height}",
                'thumbnail_url' => $v->thumbnailUrl(),
                'preview_url' => $v->previewUrl(),
                'url' => route('videos.show', $v),
            ]),
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }
}
