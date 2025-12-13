<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClientBoc;
use Carbon\Carbon;

class MarkAbandonedBocs extends Command
{
    protected $signature = 'bocs:mark-abandoned';
    protected $description = 'Marque les BOC pending comme abandonnés après 5 minutes';

    public function handle()
    {
        $limit = Carbon::now()->subMinutes(5);

        $count = ClientBoc::where('status', 'pending')
            ->where('created_at', '<=', $limit)
            ->update([
                'status' => 'abandoned',
            ]);

        $this->info("BOC passés en abandoned : {$count}");

        return Command::SUCCESS;
    }
}
