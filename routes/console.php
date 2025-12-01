<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule PM compliance updates daily at 23:55
Schedule::command('cmms:update-compliance')->dailyAt('23:55');

// Send PM reminders daily at 08:00 (for PMs scheduled tomorrow)
Schedule::command('cmms:send-pm-reminders')->dailyAt('08:00');

// Send overdue PM alerts daily at 09:00
Schedule::command('cmms:send-overdue-pm-alerts')->dailyAt('09:00');
