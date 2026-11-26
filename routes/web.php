<?php

use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::controller(VideoController::class)->group(function () {
    Route::get('/', 'index')->name('videos.index');
    Route::get('/upload', 'create')->name('videos.create');
    Route::post('/videos', 'store')->middleware('throttle:10,1')->name('videos.store');
    Route::get('/videos/{video}', 'show')->name('videos.show');
    Route::get('/videos/{video}/status', 'status')->name('videos.status');
    Route::get('/videos/{video}/stream', 'stream')->name('videos.stream');

    Route::get('/api/videos', 'apiIndex')->name('api.videos.index');
});
