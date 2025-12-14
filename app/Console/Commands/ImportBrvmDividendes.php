<?php

namespace App\Console\Commands;

use App\Services\BrvmDividendeService;


use Illuminate\Console\Command;

class ImportBrvmDividendes extends Command
{
    protected $signature = 'import:brvm-dividendes
                            {storagePath : Chemin du PDF sur le disk public (ex: bocs/BOC_20251202.pdf)}
                            {--date= : Date de référence du BOC (YYYY-MM-DD) ex: 2025-12-02}';

    protected $description = 'Importe les infos dividendes (pages 3-4) d’un BOC et les stocke en base';

    public function handle(BrvmDividendeService $service): int
    {
        $path = $this->argument('storagePath');
        $date = $this->option('date');

        $this->info("Import dividendes depuis: {$path}");
        if ($date) $this->info("Date référence: {$date}");

        $count = $service->upsertFromBoc($path, $date);

        $this->info("OK - lignes upsert: {$count}");
        return self::SUCCESS;
    }
}
