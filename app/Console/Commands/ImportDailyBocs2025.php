<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\DailyBoc;
use App\Models\BocStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\BrvmBubbleService;

class ImportDailyBocs2025 extends Command
{
    protected $signature = 'import:bocs {dir : Dossier contenant les BOC_YYYYMMDD.pdf} {--from=} {--to=}';
    protected $description = 'Importe en lot des BOC PDF (DailyBoc + extraction variations BocStock)';

    // ✅ Mets ici tes fériés (2025/2026 etc). Tu peux copier ceux de ton script python.
    private function holidays(): array
    {
        return [
            '2025-01-01',
            '2025-03-28',
            '2025-03-31',
            '2025-04-21',
            '2025-05-01',
            '2025-05-29',
            '2025-06-06',
            '2025-06-09',
            '2025-08-07',
            '2025-08-15',
            '2025-09-05',
            '2025-12-25',
        ];
    }

    public function handle(BrvmBubbleService $bubble)
    {
        $dir = $this->argument('dir');
        if (!is_dir($dir)) {
            $this->error("Dossier introuvable: {$dir}");
            return 1;
        }

        $from = $this->option('from') ? Carbon::parse($this->option('from'))->startOfDay() : null;
        $to   = $this->option('to')   ? Carbon::parse($this->option('to'))->startOfDay()   : null;

        $files = glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'BOC_*.pdf');
        sort($files);

        if (empty($files)) {
            $this->warn("Aucun fichier BOC_*.pdf trouvé dans: {$dir}");
            return 0;
        }

        $holidays = $this->holidays();
        $ok = 0; $skip = 0; $fail = 0;

        foreach ($files as $filePath) {
            $base = basename($filePath);

            // attend BOC_YYYYMMDD.pdf
            if (!preg_match('/BOC_(\d{8})\.pdf$/i', $base, $m)) {
                $this->warn("SKIP nom invalide: {$base}");
                $skip++;
                continue;
            }

            $date = Carbon::createFromFormat('Ymd', $m[1])->startOfDay();
            $dateString = $date->toDateString();

            if ($from && $date->lt($from)) continue;
            if ($to && $date->gt($to)) continue;

            if ($date->isWeekend()) {
                $this->line("SKIP weekend {$dateString}");
                $skip++;
                continue;
            }

            if (in_array($dateString, $holidays, true)) {
                $this->line("SKIP férié {$dateString}");
                $skip++;
                continue;
            }

            if (DailyBoc::where('date_boc', $dateString)->exists()) {
                $this->line("SKIP déjà en base {$dateString}");
                $skip++;
                continue;
            }

            try {
                // 1) copier le pdf vers storage public/bocs
                $storedPath = Storage::disk('public')->putFileAs(
                    'bocs',
                    new \Illuminate\Http\File($filePath),
                    $base
                );

                // 2) créer DailyBoc
                $dailyBoc = DailyBoc::create([
                    'date_boc'      => $dateString,
                    'file_path'     => $storedPath,
                    'original_name' => $base,
                ]);

                // 3) extraction variations + insert boc_stocks
                $stocks = $bubble->extractFromBoc($dailyBoc->file_path);

                DB::transaction(function () use ($dailyBoc, $stocks) {
                    BocStock::where('daily_boc_id', $dailyBoc->id)->delete();

                    $rows = [];
                    foreach ($stocks as $s) {
                        $ticker = strtoupper(trim($s['ticker'] ?? ''));
                        if ($ticker === '') continue;

                        $rows[] = [
                            'daily_boc_id' => $dailyBoc->id,
                            'date_boc'     => $dailyBoc->date_boc,
                            'ticker'       => $ticker,
                            'name'         => $s['name'] ?? null,
                            'price'        => $s['price'] ?? null,
                            'change'       => $s['change'] ?? null,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ];
                    }
                    if (!empty($rows)) BocStock::insert($rows);
                });

                $this->info("OK {$dateString} ({$base})");
                $ok++;

            } catch (\Throwable $e) {
                $this->error("FAIL {$dateString} {$base} => ".$e->getMessage());
                Log::error("Import BOC fail {$dateString} {$base}: ".$e->getMessage());
                $fail++;
            }
        }

        $this->line("-----");
        $this->info("Import terminé ✅ OK={$ok} | SKIP={$skip} | FAIL={$fail}");

        return 0;
    }
}
