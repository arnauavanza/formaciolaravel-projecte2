<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('tickets:auto-close')
    ->dailyAt('02:00')
    ->withoutOverlapping();

Schedule::command('sanctum:prune-expired')
    ->dailyAt('03:00')
    ->withoutOverlapping();
