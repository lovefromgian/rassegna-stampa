<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scansione giornaliera delle rassegne attive (regole-business.md §2): una volta al giorno.
Schedule::command('rassegne:scansiona')->dailyAt('08:00');
