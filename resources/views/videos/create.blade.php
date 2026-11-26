@extends('layout')

@section('title', 'Upload · ClipForge')

@section('content')
    <h2>Upload video</h2>

    <form class="upload" id="upload-form" method="post" action="{{ route('videos.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="text" name="title" placeholder="Title" maxlength="120" required value="{{ old('title') }}">
        <input type="file" name="file" accept="video/mp4,video/quicktime,video/webm,video/x-matroska" required>
        <div class="bar" hidden><i></i></div>
        <div class="error" id="upload-error">
            @foreach ($errors->all() as $error) {{ $error }}<br> @endforeach
        </div>
        <button class="btn" type="submit">Upload</button>
        <small class="muted">MP4, MOV, WebM or MKV, up to {{ intdiv(config('clipforge.upload.max_kb'), 1024) }} MB.</small>
    </form>
@endsection

@push('scripts')
<script>
    // Progressive enhancement: AJAX upload with a progress bar.
    // Without JS the form still posts normally and the server redirects.
    $('#upload-form').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this), $bar = $form.find('.bar'), $btn = $form.find('button'), $err = $('#upload-error');

        $err.empty();
        $btn.prop('disabled', true);
        $bar.prop('hidden', false).find('i').css('width', 0);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { Accept: 'application/json' },
            xhr: function () {
                var xhr = $.ajaxSettings.xhr();
                xhr.upload.addEventListener('progress', function (ev) {
                    if (ev.lengthComputable) $bar.find('i').css('width', (ev.loaded / ev.total * 100) + '%');
                });
                return xhr;
            }
        }).done(function (res) {
            window.location = res.url;
        }).fail(function (xhr) {
            var errors = (xhr.responseJSON && xhr.responseJSON.errors) || { upload: ['Upload failed (' + xhr.status + ')'] };
            $.each(errors, function (_, list) { $.each(list, function (_, msg) { $('<div>').text(msg).appendTo($err); }); });
            $btn.prop('disabled', false);
        });
    });
</script>
@endpush
