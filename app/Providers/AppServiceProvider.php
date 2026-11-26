<?php

namespace App\Providers;

use App\Services\VideoCatalog;
use App\Services\ViewCounter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VideoCatalog::class);
        $this->app->singleton(ViewCounter::class);
    }

    public function boot(): void
    {
        // Catch N+1 queries and silently dropped attributes during development.
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
