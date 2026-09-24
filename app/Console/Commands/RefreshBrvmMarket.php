<?php

namespace App\Console\Commands;

use App\Services\BrvmMarketSnapshot;
use Illuminate\Console\Command;

class RefreshBrvmMarket extends Command
{
    protected $signature = 'brvm:refresh-market';

    protected $description = 'Rafraîchit le relevé des cours brvm.org lu par le bandeau et l\'accueil';

    public function handle(BrvmMarketSnapshot $snapshot): int
    {
        $count = $snapshot->refresh();

        if ($count === 0) {
            $this->warn('brvm.org sans données : relevé précédent conservé ('
                . ($snapshot->fetchedAt()?->toDateTimeString() ?? 'aucun') . ').');

            return self::FAILURE;
        }

        $this->info("Relevé mis à jour : {$count} lignes.");

        return self::SUCCESS;
    }
}
