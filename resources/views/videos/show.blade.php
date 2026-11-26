@extends('layout')

@section('title', $video->title.' · ClipForge')

@section('content')
    <h2>{{ $video->title }}</h2>

    <div id="player" data-status-url="{{ route('videos.status', $video) }}" data-status="{{ $video->status->value }}">
        @if ($video->status === \App\Enums\VideoStatus::Ready)
            <video controls preload="metadata" poster="{{ $video->thumbnailUrl() }}" style="width:100%;max-width:960px;border-radius:10px;background:#000">
                <source src="{{ route('videos.stream', $video) }}">
            </video>
            <p class="muted">
                {{ $video->durationLabel() }} · {{ $video->width }}x{{ $video->height }}
                · {{ number_format($video->size_bytes / 1048576, 1) }} MB · {{ number_format($video->views) }} views
            </p>
        @elseif ($video->status === \App\Enums\VideoStatus::Failed)
            <p class="error">Processing failed. The file may be corrupted or not a video.</p>
        @else
            <p class="muted" id="processing">Processing… generating thumbnail and preview. This page refreshes automatically.</p>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    (function poll() {
        var $player = $('#player');
        if (['pending', 'processing'].indexOf($player.data('status')) === -1) return;

        var delay = 2000;
        (function tick() {
            $.getJSON($player.data('status-url')).done(function (res) {
                if (res.final) { window.location.reload(); return; }
                delay = Math.min(delay * 1.5, 10000); // back off so a long job doesn't hammer the server
                setTimeout(tick, delay);
            }).fail(function () { setTimeout(tick, 10000); });
        })();
    })();
</script>
@endpush
