<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\BrvmCalendarService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('payments:notify-abandoned')->everyFiveMinutes();

// Relevé des cours brvm.org (bandeau, accueil) : toutes les 10 min pendant la
// séance (jours ouvrés BRVM, heure d'Abidjan), plus un passage après la clôture.
$jourDeSeance = fn () => app(BrvmCalendarService::class)->isTradingDay(now('Africa/Abidjan'));

Schedule::command('brvm:refresh-market')
    ->timezone('Africa/Abidjan')
    ->weekdays()
    ->everyTenMinutes()
    ->between('9:00', '15:40')
    ->when($jourDeSeance)
    ->withoutOverlapping();

Schedule::command('brvm:refresh-market')
    ->timezone('Africa/Abidjan')
    ->weekdays()
    ->at('16:00')
    ->when($jourDeSeance)
    ->withoutOverlapping();

// Affiliation : passe les commissions pending → validated après security_delay_days jours
Schedule::command('affiliate:validate-commissions')->daily();
