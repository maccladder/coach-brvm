<?php

namespace App\Http\Controllers;

use App\Models\BocStock;
use App\Models\ClientBoc;
use App\Models\ClientFinancial;
use App\Models\DailyBoc;
use App\Services\AiInterpreter;
use App\Services\AiVoiceService;
use App\Services\AvatarService;
use App\Services\BrvmBubbleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /** Page de login admin */
    public function showLoginForm()
    {
        return view('admin.login');
    }

    /**
     * ✅ Remplacer une BOC + recalculer BocStock + republier la version publique
     */
    public function dailyBocsReplace(
        Request $request,
        DailyBoc $dailyBoc,
        BrvmBubbleService $bubble,
        AiInterpreter $ai,
        AiVoiceService $voice,
        AvatarService $avatar
    ) {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $dateString = Carbon::parse($dailyBoc->date_boc)->toDateString();

        DB::transaction(function () use ($request, $dailyBoc, $bubble) {

            // 1) Sauvegarder l'ancien chemin
            $oldPath = $dailyBoc->file_path;

            // 2) Stocker le nouveau PDF
            $newPath = $request->file('file')->store('bocs', 'public');

            // 3) Mettre à jour le DailyBoc
            $dailyBoc->previous_file_path = $oldPath;
            $dailyBoc->file_path = $newPath;
            $dailyBoc->original_name = $request->file('file')->getClientOriginalName();
            $dailyBoc->replaced_at = now();
            $dailyBoc->replaced_by = auth()->id(); // si admin est connecté via auth
            $dailyBoc->save();

            // 4) Re-extraction + rewrite BocStock
            $stocks = $bubble->extractFromBoc($dailyBoc->file_path);

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

            // OPTION: supprimer l'ancien fichier
            // if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            //     Storage::disk('public')->delete($oldPath);
            // }
        });

        // ✅ IMPORTANT : on republie APRES la transaction (quand tout est OK)
        $this->publishDailyBocAsPublic($dailyBoc, $ai, $voice, $avatar);

        return back()->with('success', "✅ BOC du {$dateString} remplacée + données recalculées + publiée (public).");
    }

    /** Vérification du code admin */
    public function login(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        if ($request->code !== 'Coach-brvm2025') {
            return back()
                ->withInput()
                ->with('error', 'Code incorrect.');
        }

        session(['is_admin' => true]);

        return redirect()->route('admin.dashboard');
    }

    /** Déconnexion admin */
    public function logout(Request $request)
    {
        $request->session()->forget('is_admin');

        return redirect()->route('admin.login.form')
            ->with('success', 'Déconnecté avec succès.');
    }

    /** Dashboard admin */
    public function dashboard()
    {
        $bocs = ClientBoc::orderByDesc('created_at')->limit(50)->get();
        $financials = ClientFinancial::orderByDesc('created_at')->limit(50)->get();

        return view('admin.dashboard', compact('bocs', 'financials'));
    }

    /**
     * Jours fériés BRVM / Côte d'Ivoire
     * Format : YYYY-MM-DD
     */
    private function getBrvmHolidays(): array
    {
        return [
            // ✅ 2025
            '2025-01-01',
            '2025-03-27',
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

            // ✅ 2026
            '2026-01-01',
            '2026-03-17',
            '2026-03-20',
            '2026-04-06',
            '2026-05-01',
            '2026-05-14',
            '2026-05-25',
            '2026-05-27',
            '2026-08-07',
            '2026-08-15',
            '2026-08-26',
            '2026-11-01',
            '2026-11-15',
            '2026-12-25',
        ];
    }

    public function dailyBocsIndex(Request $request)
    {
        $startDate = Carbon::create(2025, 1, 1)->startOfDay();
        $today     = Carbon::today();

        $holidays = $this->getBrvmHolidays();

        $bocs = DailyBoc::whereBetween('date_boc', [$startDate, $today])
            ->get()
            ->keyBy(fn ($boc) => Carbon::parse($boc->date_boc)->toDateString());

        $daysAll = [];
        $current = $startDate->copy();

        while ($current->lte($today)) {
            $key = $current->toDateString();

            if ($current->isWeekend()) {
                $current->addDay();
                continue;
            }

            if (in_array($key, $holidays, true)) {
                $current->addDay();
                continue;
            }

            $record  = $bocs->get($key);
            $isToday = $current->isSameDay($today);

            $daysAll[] = [
                'date'       => $current->copy(),
                'record'     => $record,
                'has_boc'    => (bool) $record,
                'is_today'   => $isToday,
                'is_missing' => !$record && !$isToday,
            ];

            $current->addDay();
        }

        $daysCollection = collect($daysAll)
            ->sortByDesc(fn ($d) => $d['date']->timestamp)
            ->values();

        $stats = [
            'total_days' => $daysCollection->count(),
            'received'   => $daysCollection->where('has_boc', true)->count(),
            'missing'    => $daysCollection->where('is_missing', true)->count(),
        ];

        $perPage = (int) $request->query('per_page', 60);
        $page    = (int) $request->query('page', 1);

        $itemsForCurrentPage = $daysCollection
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        $days = new LengthAwarePaginator(
            $itemsForCurrentPage,
            $daysCollection->count(),
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.daily_bocs', compact('days', 'today', 'stats', 'perPage'));
    }

    /**
     * ✅ Publier la BOC “du jour” comme BOC public (ClientBoc) + regen analyse/audio/avatar
     */
    private function publishDailyBocAsPublic(
        DailyBoc $dailyBoc,
        AiInterpreter $ai,
        AiVoiceService $voice,
        AvatarService $avatar
    ): void {
        // 1) Mettre tous les anciens à false (un seul public à la fois)
        ClientBoc::where('is_public', true)->update(['is_public' => false]);

        // 2) Upsert du “public boc” lié à ce dailyBoc
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

        // 🔥 si replace PDF, on force regen
        $clientBoc->interpreted_markdown = null;
        $clientBoc->audio_path           = null;
        $clientBoc->avatar_video_url     = null;

        $clientBoc->save();

        // 3) Générer analyse + audio + avatar
        $this->generateAnalysisForBoc($clientBoc, $ai, $voice, $avatar);
    }

    private function generateAnalysisForBoc(
        ClientBoc $clientBoc,
        AiInterpreter $ai,
        AiVoiceService $voice,
        AvatarService $avatar
    ): void {
        // ✅ robust: boc_date peut être string ou date
        $bocDate = Carbon::parse($clientBoc->boc_date)->toDateString();

        $analyses = [[
            'title'     => $clientBoc->title,
            'file_path' => $clientBoc->stored_path,
            'notes'     => null,
        ]];
        $statements = [];

        $interpretation = $ai->interpret($analyses, $statements, $bocDate);
        $clientBoc->interpreted_markdown = $interpretation;

        // Texte raccourci pour l'avatar
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
Bonjour, je suis ton coach BRVM.

Voici les principaux enseignements de ton BOC du {$bocDate} :
{$mainSummary}

N'oublie pas : ceci n'est pas un conseil d'investissement personnalisé.
Analyse toujours toi-même les entreprises et n'investis que l'argent que tu peux te permettre de perdre.
TXT;

        $textForAvatar = mb_substr($textForAvatar, 0, 900);

        // Avatar
        $avatarUrl = $avatar->generateTalkingHead($textForAvatar);
        Log::info('Avatar URL pour BOC '.$clientBoc->id.' = '.($avatarUrl ?: 'NULL'));
        if ($avatarUrl) {
            $clientBoc->avatar_video_url = $avatarUrl;
        }

        // Audio
        $audioPath = $voice->makeAudioFromMarkdown(
            $clientBoc->interpreted_markdown ?? '',
            'clientboc-' . $clientBoc->id
        );
        $clientBoc->audio_path = $audioPath;

        $clientBoc->save();
    }

    /** ✅ Upload d'une BOC + extraction + publication public */
    public function dailyBocsStore(
        Request $request,
        BrvmBubbleService $bubble,
        AiInterpreter $ai,
        AiVoiceService $voice,
        AvatarService $avatar
    ) {
        $request->validate([
            'date_boc' => ['required', 'date', 'after_or_equal:2025-01-01', 'before_or_equal:today'],
            'file'     => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $date       = Carbon::parse($request->input('date_boc'));
        $dateString = $date->toDateString();

        $holidays = $this->getBrvmHolidays();

        if ($date->isWeekend()) {
            return back()->with('error', "Il n'y a pas de BOC les samedis et dimanches.");
        }

        if (in_array($dateString, $holidays, true)) {
            return back()->with('error', "Il n'y a pas de BOC les jours fériés officiels (BRVM / Côte d'Ivoire).");
        }

        if (DailyBoc::where('date_boc', $dateString)->exists()) {
            return back()->with('error', "Une BOC existe déjà pour la date {$dateString}.");
        }

        $path = $request->file('file')->store('bocs', 'public');

        $dailyBoc = DailyBoc::create([
            'date_boc'      => $dateString,
            'file_path'     => $path,
            'original_name' => $request->file('file')->getClientOriginalName(),
        ]);

        try {
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

                if (!empty($rows)) {
                    BocStock::insert($rows);
                }
            });

            // ✅ Publication public à la fin (succès extraction)
            $this->publishDailyBocAsPublic($dailyBoc, $ai, $voice, $avatar);

            return back()->with('success', "BOC du {$dateString} enregistrée + variations extraites + publiée (public) ✅");
        } catch (\Throwable $e) {
            Log::error("Extraction variations échouée (DailyBoc {$dailyBoc->id}) : " . $e->getMessage());

            // ✅ même si extraction échoue, tu peux décider de publier ou non.
            // Ici je publie quand même, car tu veux la BOC publique du jour.
            $this->publishDailyBocAsPublic($dailyBoc, $ai, $voice, $avatar);

            return back()->with('success',
                "BOC du {$dateString} enregistrée ✅ (extraction variations a échoué — voir logs) + publiée (public)."
            );
        }
    }
}
