<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        // Originals: private, streamed through the app.
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'throw' => false,
        ],

        // Derived assets (thumbnails, previews): served directly by nginx.
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
