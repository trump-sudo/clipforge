<?php

return [

    /*
    | Binaries used by the processing pipeline. Override when they are not on PATH
    | (ImageMagick 7 ships `magick`, ImageMagick 6 ships `convert`).
    */
    'ffmpeg' => env('FFMPEG_BIN', 'ffmpeg'),
    'ffprobe' => env('FFPROBE_BIN', 'ffprobe'),
    'imagemagick' => env('IMAGEMAGICK_BIN', 'convert'),

    'upload' => [
        'max_kb' => (int) env('UPLOAD_MAX_KB', 512000),
        'mimetypes' => ['video/mp4', 'video/quicktime', 'video/webm', 'video/x-matroska'],
    ],

    'thumbnail' => [
        'width' => 640,
        'height' => 360,
        'quality' => 82,
    ],

    // Hover preview ("teaser"), the classic tube-site clip: N short segments glued together.
    'preview' => [
        'segments' => 3,
        'segment_seconds' => 2,
        'width' => 480,
        'crf' => 28,
    ],

    'catalog' => [
        'per_page' => 12,
        'ttl' => (int) env('CATALOG_CACHE_TTL', 300),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],
];
