<?php

namespace App\Console\Commands;

use App\Services\Affiliate\AffiliateCommissionService;
use Illuminate\Console\Command;

class AffiliateValidateCommissions extends Command
{
    protected $signature   = 'affiliate:validate-commissions';
    protected $description = 'Passe les commissions pending de plus de security_delay_days jours à validated (retirable).';

    public function handle(AffiliateCommissionService $service): int
    {
        $count = $service->validatePendingCommissions();

        $this->info("✅ {$count} commission(s) passée(s) à validated.");

        return Command::SUCCESS;
    }
}
