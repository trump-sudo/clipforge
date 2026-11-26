@extends('layout')

@section('content')
    @if ($videos->isEmpty())
        <p class="muted">No videos yet. <a href="{{ route('videos.create') }}" style="color:var(--accent)">Upload the first one</a>.</p>
    @else
        <div class="grid">
            @foreach ($videos as $video)
                <a class="card" href="{{ route('videos.show', $video) }}">
                    <div class="thumb" data-preview="{{ $video->previewUrl() }}">
                        <img src="{{ $video->thumbnailUrl() }}" alt="{{ $video->title }}" loading="lazy">
                        <span class="dur">{{ $video->durationLabel() }}</span>
                    </div>
                    <div class="meta">
                        <div>{{ $video->title }}</div>
                        <small>{{ $video->width }}x{{ $video->height }} · {{ number_format($video->views) }} views · {{ $video->created_at->diffForHumans() }}</small>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="pagination">
            @if ($videos->previousPageUrl()) <a class="btn" href="{{ $videos->previousPageUrl() }}">← Prev</a> @endif
            @if ($videos->nextPageUrl()) <a class="btn" href="{{ $videos->nextPageUrl() }}">Next →</a> @endif
        </div>
    @endif
@endsection

@push('scripts')
<script>
    // Hover preview: the <video> element is created lazily on first hover,
    // so the grid page downloads only thumbnails.
    $(document).on('mouseenter', '.thumb[data-preview]', function () {
        var $thumb = $(this), $video = $thumb.find('video');
        if (!$video.length) {
            $video = $('<video muted loop playsinline preload="auto">').attr('src', $thumb.data('preview')).appendTo($thumb);
            $video.on('playing', function () { $thumb.addClass('playing'); });
        }
        var p = $video[0].play();
        if (p && p.catch) p.catch(function () {});
    }).on('mouseleave', '.thumb[data-preview]', function () {
        var $thumb = $(this), video = $thumb.find('video')[0];
        $thumb.removeClass('playing');
        if (video) { video.pause(); video.currentTime = 0; }
    });
</script>
@endpush
