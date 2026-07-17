<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('cms:publish-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('cms:purge-contact-data')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('cms:backup --type=database')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('cms:backup --type=full')->weeklyOn(1, '02:30')->withoutOverlapping();
Schedule::command('cms:prune')->weeklyOn(1, '04:00')->withoutOverlapping();
