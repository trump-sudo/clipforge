<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Write-behind view counter.
 *
 * Every page view is a single O(1) HINCRBY in Redis instead of an UPDATE on a hot
 * row in MySQL. A scheduled command moves the accumulated deltas into the database
 * once a minute in one batch.
 */
class ViewCounter
{
    private const HASH = 'views:pending';

    public function hit(Video $video): void
    {
        Redis::hincrby(self::HASH, (string) $video->id, 1);
    }

    /**
     * @return int number of videos updated
     */
    public function flush(): int
    {
        if (! Redis::exists(self::HASH)) {
            return 0;
        }

        // RENAME is atomic: new hits keep landing in a fresh hash while we drain this one.
        $batchKey = self::HASH.':'.uniqid('', true);
        Redis::rename(self::HASH, $batchKey);

        $deltas = Redis::hgetall($batchKey);

        DB::transaction(function () use ($deltas) {
            foreach ($deltas as $id => $delta) {
                // Query builder on purpose: no model events, no cache flush for every counter bump.
                DB::table('videos')->where('id', (int) $id)->increment('views', (int) $delta);
            }
        });

        Redis::del($batchKey);

        return count($deltas);
    }
}
