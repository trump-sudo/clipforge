<?php

namespace App\Console\Commands;

use App\Services\ViewCounter;
use Illuminate\Console\Command;

class FlushVideoViews extends Command
{
    protected $signature = 'videos:flush-views';

    protected $description = 'Move buffered view counts from Redis into the database';

    public function handle(ViewCounter $counter): int
    {
        $updated = $counter->flush();
        $this->info("Flushed views for {$updated} video(s).");

        return self::SUCCESS;
    }
}
