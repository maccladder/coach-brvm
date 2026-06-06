<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('payments:notify-abandoned')->everyFiveMinutes();

// Affiliation : passe les commissions pending → validated après security_delay_days jours
Schedule::command('affiliate:validate-commissions')->daily();
