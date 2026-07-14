<?php

namespace App\Services;

use App\Models\BocStock;
use App\Models\ClientBoc;
use App\Models\DailyBoc;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pipeline d'ingestion d'une BOC : stockage du PDF, création du DailyBoc,
 * extraction des cours (BocStock), puis publication comme BOC publique
 * (ClientBoc + analyse IA + audio + avatar).
 *
 * Factorisé depuis AdminController pour être réutilisé à l'identique par
 * l'upload manuel (/admin/bocs), le remplacement (/admin/daily-bocs/{id}/replace)
 * et l'agent n8n (/api/n8n/bocs).
 */
class DailyBocIngestionService
{
    public function __construct(
        private BrvmBubbleService $bubble,
        private AiInterpreter $ai,
        private AiVoiceService $voice,
        private AvatarService $avatar,
    ) {
    }

    /**
     * @return array{boc: DailyBoc, extracted: bool}
     */
    public function ingest(string $dateString, UploadedFile $file, ?string $originalName = null): array
    {
        $path = $file->store('bocs', 'public');

        $dailyBoc = DailyBoc::create([
            'date_boc'      => $dateString,
            'file_path'     => $path,
            'original_name' => $originalName ?? $file->getClientOriginalName(),
        ]);

        $extracted = $this->extractStocks($dailyBoc);

        $this->publish($dailyBoc);

        return ['boc' => $dailyBoc, 'extracted' => $extracted];
    }

    private function extractStocks(DailyBoc $dailyBoc): bool
    {
        try {
            $stocks = $this->bubble->extractFromBoc($dailyBoc->file_path);

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

                if (!empty($rows)) {
                    BocStock::insert($rows);
                }
            });

            return true;
        } catch (\Throwable $e) {
            Log::error("Extraction variations échouée (DailyBoc {$dailyBoc->id}) : " . $e->getMessage());

            return false;
        }
    }

    /**
     * Publier la BOC "du jour" comme BOC publique (ClientBoc) + régénérer analyse/audio/avatar.
     */
    public function publish(DailyBoc $dailyBoc): void
    {
        ClientBoc::where('is_public', true)->update(['is_public' => false]);

        $bocDate = Carbon::parse($dailyBoc->date_boc)->toDateString();

        $clientBoc = ClientBoc::firstOrNew([
            'daily_boc_id' => $dailyBoc->id,
        ]);

        $clientBoc->title             = "BOC du {$bocDate}";
        $clientBoc->boc_date          = $bocDate;
        $clientBoc->original_filename = $dailyBoc->original_name;
        $clientBoc->stored_path       = $dailyBoc->file_path;
        $clientBoc->file_path         = $dailyBoc->file_path;
        $clientBoc->amount            = 0;
        $clientBoc->status            = 'paid';
        $clientBoc->transaction_id    = $clientBoc->transaction_id ?: Str::uuid()->toString();
        $clientBoc->is_public         = true;

        // si replace/ré-ingestion, on force la régénération
        $clientBoc->interpreted_markdown = null;
        $clientBoc->audio_path           = null;
        $clientBoc->avatar_video_url     = null;

        $clientBoc->save();

        $this->generateAnalysisForBoc($clientBoc);
    }

    private function generateAnalysisForBoc(ClientBoc $clientBoc): void
    {
        $bocDate = Carbon::parse($clientBoc->boc_date)->toDateString();

        $analyses = [[
            'title'     => $clientBoc->title,
            'file_path' => $clientBoc->stored_path,
            'notes'     => null,
        ]];
        $statements = [];

        $interpretation = $this->ai->interpret($analyses, $statements, $bocDate);
        $clientBoc->interpreted_markdown = $interpretation;

        $plain = $interpretation ?? '';
        $plain = preg_replace('/^\s*#+\s*/m', '', $plain);
        $plain = preg_replace('/^\s*[-*]\s+/m', '', $plain);
        $plain = str_replace(['**', '*', '_', '`'], '', $plain);
        $plain = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $plain);
        $plain = preg_replace("/\n{2,}/", "\n", $plain);
        $plain = trim($plain);

        $lines       = array_filter(array_map('trim', explode("\n", $plain)));
        $mainLines   = array_slice($lines, 0, 4);
        $mainSummary = implode(' ', $mainLines);

        $textForAvatar = <<<TXT
Bonjour, je suis ton coach Boursiv.

Voici les principaux enseignements de ton BOC du {$bocDate} :
{$mainSummary}

N'oublie pas : ceci n'est pas un conseil d'investissement personnalisé.
Analyse toujours toi-même les entreprises et n'investis que l'argent que tu peux te permettre de perdre.
TXT;

        $textForAvatar = mb_substr($textForAvatar, 0, 900);

        $avatarUrl = $this->avatar->generateTalkingHead($textForAvatar);
        Log::info('Avatar URL pour BOC '.$clientBoc->id.' = '.($avatarUrl ?: 'NULL'));
        if ($avatarUrl) {
            $clientBoc->avatar_video_url = $avatarUrl;
        }

        $audioPath = $this->voice->makeAudioFromMarkdown(
            $clientBoc->interpreted_markdown ?? '',
            'clientboc-' . $clientBoc->id
        );
        $clientBoc->audio_path = $audioPath;

        $clientBoc->save();
    }
}
