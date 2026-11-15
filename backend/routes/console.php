<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// RO07 : rappels check-in à l'hôte (48 h) et au voyageur (24 h).
Schedule::command('reminders:send')->dailyAt('09:00')->withoutOverlapping();
