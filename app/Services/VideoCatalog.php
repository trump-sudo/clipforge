<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Cached public catalog.
 *
 * Invalidation uses a version counter instead of deleting keys one by one:
 * flush() bumps the version, so every cached page becomes unreachable at once
 * and expires by TTL. Works with any cache store (no tags required).
 */
class VideoCatalog
{
    private const VERSION_KEY = 'catalog:version';

    public function page(int $page = 1): LengthAwarePaginator
    {
        $perPage = (int) config('clipforge.catalog.per_page');
        $page = max(1, $page);
        $key = sprintf('catalog:v%d:page:%d', $this->version(), $page);

        $cached = Cache::remember($key, config('clipforge.catalog.ttl'), function () use ($page, $perPage) {
            $query = Video::query()->ready();

            return [
                'total' => (clone $query)->count(),
                'items' => $query->latest()->forPage($page, $perPage)->get(),
            ];
        });

        return new LengthAwarePaginator(
            $cached['items'],
            $cached['total'],
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
    }

    public function flush(): void
    {
        $this->version();
        Cache::increment(self::VERSION_KEY);
    }

    private function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }
}
