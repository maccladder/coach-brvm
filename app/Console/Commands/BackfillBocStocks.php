<?php

namespace App\Console\Commands;

use App\Models\DailyBoc;
use App\Models\BocStock;
use App\Services\BrvmBubbleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillBocStocks extends Command
{
    protected $signature = 'boc:backfill-stocks {--from= : Date début YYYY-MM-DD} {--to= : Date fin YYYY-MM-DD} {--force : Recalculer même si déjà rempli}';
    protected $description = 'Remplit la table boc_stocks à partir des DailyBoc déjà uploadées (pages 3&4).';

    public function handle(BrvmBubbleService $bubble): int
    {
        $from = $this->option('from');
        $to   = $this->option('to');
        $force = (bool) $this->option('force');

        $q = DailyBoc::query()->orderBy('date_boc');

        if ($from) $q->where('date_boc', '>=', $from);
        if ($to)   $q->where('date_boc', '<=', $to);

        $bocs = $q->get();

        if ($bocs->isEmpty()) {
            $this->warn("Aucune DailyBoc trouvée dans cette plage.");
            return self::SUCCESS;
        }

        $this->info("DailyBoc trouvées: " . $bocs->count());

        $ok = 0; $skip = 0; $fail = 0;

        foreach ($bocs as $boc) {
            try {
                // si déjà rempli et pas force -> skip
                $already = BocStock::where('daily_boc_id', $boc->id)->exists();
                if ($already && !$force) {
                    $this->line("⏭️  {$boc->date_boc} (ID {$boc->id}) déjà rempli, skip");
                    $skip++;
                    continue;
                }

                $this->line("⚙️  Extraction {$boc->date_boc} (ID {$boc->id}) ...");

                $stocks = $bubble->extractFromBoc($boc->file_path);

                DB::transaction(function () use ($boc, $stocks) {
                    BocStock::where('daily_boc_id', $boc->id)->delete();

                    $rows = [];
                    foreach ($stocks as $s) {
                        $ticker = strtoupper(trim($s['ticker'] ?? ''));
                        if ($ticker === '') continue;

                        $rows[] = [
                            'daily_boc_id' => $boc->id,
                            'date_boc'     => $boc->date_boc,
                            'ticker'       => $ticker,
                            'name'         => $s['name'] ?? null,
                            'price'        => $s['price'] ?? null,
                            'change'       => $s['change'] ?? null,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ];
                    }

                    if (!empty($rows)) {
                        BocStock::insert($rows);
                    }
                });

                $count = BocStock::where('daily_boc_id', $boc->id)->count();
                $this->info("✅ {$boc->date_boc} rempli : {$count} lignes");
                $ok++;

            } catch (\Throwable $e) {
                $this->error("❌ {$boc->date_boc} échec : " . $e->getMessage());
                Log::error("Backfill BocStock échec DailyBoc {$boc->id}: " . $e->getMessage());
                $fail++;
            }
        }

        $this->newLine();
        $this->info("Terminé ✅ OK={$ok} | SKIP={$skip} | FAIL={$fail}");

        return self::SUCCESS;
    }
}
