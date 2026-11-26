<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('videos:flush-views')->everyMinute()->withoutOverlapping();
