<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('accounting:process-recurring')->dailyAt('00:30');
Schedule::command('invoice-reminders:send')->dailyAt('08:00');
Schedule::command('project-alerts:send-deadline')->dailyAt('07:00');
