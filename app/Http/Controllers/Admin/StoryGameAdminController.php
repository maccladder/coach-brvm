<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAdminGrantNotificationJob;
use App\Models\AdminGrant;
use App\Models\GameCreditLedger;
use App\Models\GameProgress;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Services\Games\StoryGameService;
use App\Services\Payments\Games\StoryGameHintsService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Admin d'un jeu à histoire (/admin/akwaba, /admin/le-pacte ; middleware admin.code) : joueurs,
 * entonnoirs, ventes, historique des indices, et gestes manuels (déblocage, indices, rattrapage
 * d'un pack). Chaque jeu fournit son service, son service d'indices et sa configuration
 * (noms, points de sauvegarde, fins, étapes de l'entonnoir) ; la vue admin.story-game est commune.
 */
abstract class StoryGameAdminController extends Controller
{
    abstract protected function game(): StoryGameService;

    abstract protected function hintsService(): StoryGameHintsService;

    /**
     * Configuration du jeu pour le contrôleur et la vue :
     *  name, title, page_title, route (préfixe des routes admin), play_route, hints_prefix,
     *  funnel_saves [save => libellé], short_labels [save => libellé court], lock_before (save
     *  avant lequel s'insère la ligne « Cadenas »), lock_threshold (save à partir duquel un joueur
     *  a dépassé le cadenas), lock_help, fins [clé => nom], loaded_types, details [[clé, libellé,
     *  types, gras]], browser_stages [[clé, libellé, types]], browser_unknown_note, sessions_note,
     *  errors (section « Erreurs du jeu »).
     */
    abstract protected function config(): array;

    /** Erreurs JavaScript remontées par le jeu (section affichée si config errors). */
    protected function recentErrors(): array
    {
        return [];
    }

    /**
     * GET /admin/{jeu}?q=&user=&ledger_q=&reason=&navigateur=
     */
    public function index(Request $request)
    {
        $product = $this->game()->product();
        abort_unless($product, 404, 'Produit ' . $this->game()->slug() . ' introuvable (migration non lancée ?).');

        $selected = $request->filled('user') ? User::find((int) $request->query('user')) : null;

        return view('admin.story-game', [
            'cfg'             => $this->config(),
            'product'         => $product,
            'stats'           => $this->stats($product, $browser = $this->browserFilter($request)),
            'browser'         => $browser,
            'browsers'        => $this->browserComparison(),
            'gameErrors'      => $this->recentErrors(),
            'players'         => $this->players($request, $product, $selected),
            'ledger'          => $this->ledger($request, $selected),
            'selected'        => $selected,
            'selectedSummary' => $selected ? $this->game()->playerSummary($selected) : null,
            'duplicates'      => $this->duplicatePayments($product),
        ]);
    }

    /**
     * Déblocages payés deux fois (deux tentatives validées) : le second paiement n'a créé aucun
     * achat (PaystackMarketplaceService::DUPLICATE) et doit être remboursé au joueur.
     */
    private function duplicatePayments(MarketplaceProduct $product)
    {
        return \App\Models\Payment::query()
            ->with('user:id,name,email')
            ->where('purpose', 'marketplace')
            ->where('meta', 'like', '%"duplicate":true%')
            ->latest('id')
            ->get()
            ->filter(fn ($p) => (int) data_get($p->meta, 'product_id') === $product->id && data_get($p->meta, 'duplicate') === true)
            ->values();
    }

    /**
     * POST /admin/{jeu}/users/{user}/unlock  {reason}
     * Attribution marketplace (admin_grants), comme depuis la page Attributions.
     */
    public function unlock(Request $request, User $user)
    {
        $this->ensureFullAdmin();

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:300'],
            'amount' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);
        $product = $this->product();
        $admin   = $this->adminContext($request);
        $name    = $this->config()['name'];

        if ($this->game()->isUnlocked($user, $product)) {
            return back()->with('error', "{$user->name} a déjà l'histoire complète.");
        }

        $grant = AdminGrant::create([
            'user_id'        => $user->id,
            'grantable_type' => MarketplaceProduct::class,
            'grantable_id'   => $product->id,
            'reason'          => $name . ' : ' . $data['reason'] . ' · par ' . $admin['admin_label'],
            'amount_received' => (int) ($data['amount'] ?? 0) ?: null,
            'granted_by'      => $admin['admin_user_id'],
            'granted_at'     => now(),
        ]);

        Log::info("{$name} admin: déblocage attribué", ['grant_id' => $grant->id, 'user_id' => $user->id] + $admin);

        try {
            SendAdminGrantNotificationJob::dispatch($grant);
        } catch (\Throwable $e) {
            Log::error("{$name} admin: notification d'attribution", ['error' => $e->getMessage()]);
        }

        return back()->with('success', "Histoire complète débloquée pour {$user->name}.");
    }

    /**
     * POST /admin/{jeu}/users/{user}/lock  {reason}
     * Révoque les attributions actives. Un achat Paystack payé ne se retire pas ici.
     */
    public function lock(Request $request, User $user)
    {
        $this->ensureFullAdmin();

        $data    = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        $product = $this->product();
        $admin   = $this->adminContext($request);

        $grants = AdminGrant::where('user_id', $user->id)
            ->where('grantable_type', MarketplaceProduct::class)
            ->where('grantable_id', $product->id)
            ->active()
            ->get();

        if ($grants->isEmpty()) {
            $paid = $this->game()->playerSummary($user)['via'] === 'purchase';

            return back()->with('error', $paid
                ? "{$user->name} a payé le déblocage via Paystack : il ne se retire pas depuis cette page."
                : "{$user->name} n'a pas de déblocage attribué à la main.");
        }

        foreach ($grants as $grant) {
            $grant->update([
                'revoked_at'    => now(),
                'revoked_by'    => $admin['admin_user_id'],
                'revoke_reason' => $data['reason'] . ' · par ' . $admin['admin_label'],
            ]);
        }

        Log::info($this->config()['name'] . ' admin: déblocage retiré', ['user_id' => $user->id, 'grants' => $grants->pluck('id')] + $admin);

        $stillPaid = $this->game()->isUnlocked($user, $product);

        return back()->with('success', "Attribution retirée pour {$user->name}."
            . ($stillPaid ? ' Il garde l\'accès grâce à son achat Paystack.' : ''));
    }

    /**
     * POST /admin/{jeu}/users/{user}/hints  {count, reason}
     */
    public function addHints(Request $request, User $user)
    {
        $this->ensureFullAdmin();

        $data = $request->validate([
            'count'  => ['required', 'integer', 'min:1', 'max:100'],
            'reason' => ['required', 'string', 'max:200'],
            'amount' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);
        $admin = $this->adminContext($request);

        $balance = $this->game()->addHints(
            $user, (int) $data['count'], 'admin', null, (int) ($data['amount'] ?? 0) ?: null, $data['reason'], $admin
        );

        Log::info($this->config()['name'] . ' admin: indices ajoutés', ['user_id' => $user->id, 'count' => (int) $data['count']] + $admin);

        return back()->with('success', "+{$data['count']} indices pour {$user->name} (solde : {$balance}).");
    }

    /**
     * POST /admin/{jeu}/recover  {reference}
     * Rattrapage d'un pack d'indices payé mais non crédité : revérifié chez Paystack, crédité
     * au joueur des métadonnées, une seule fois (payment_reference unique).
     */
    public function recover(Request $request)
    {
        $this->ensureFullAdmin();

        $cfg = $this->config();
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:120', 'regex:/^' . preg_quote($cfg['hints_prefix'], '/') . '[A-Za-z0-9-]+$/'],
        ], [
            'reference.regex' => "La référence doit commencer par {$cfg['hints_prefix']} (pack d'indices {$cfg['name']}).",
        ]);
        $reference = $data['reference'];

        $existing = GameCreditLedger::where('payment_reference', $reference)->with('user')->first();
        if ($existing) {
            return back()->with('info', "Référence déjà créditée le {$existing->created_at->format('d/m/Y H:i')} à "
                . ($existing->user?->name ?? 'joueur #' . $existing->user_id) . ' : rien à faire.');
        }

        $outcome = $this->hintsService()->handle($reference, $this->adminContext($request));

        $credited = GameCreditLedger::where('payment_reference', $reference)->with('user')->first();

        return match ($outcome) {
            StoryGameHintsService::GRANTED => back()->with('success', "Pack rattrapé : +5 indices pour {$credited?->user?->name}."),
            StoryGameHintsService::ALREADY => back()->with('info', 'Référence déjà créditée entre-temps (webhook) : rien à faire.'),
            StoryGameHintsService::MISMATCH => back()->with('error', 'Paystack ne confirme pas ce pack : statut, montant (500 XOF), devise ou métadonnées incorrects. Aucun crédit.'),
            default => back()->with('error', 'Transaction introuvable chez Paystack, ou joueur inconnu. Aucun crédit.'),
        };
    }

    // ─── Données de la page ─────────────────────────────────────────────

    private function product(): MarketplaceProduct
    {
        $product = $this->game()->product();
        abort_unless($product, 404);

        return $product;
    }

    /** Gestes manuels (accès, indices, argent) : code admin seul ; le code stagiaire consulte. */
    private function ensureFullAdmin(): void
    {
        abort_unless(
            session('is_admin'),
            403,
            'Action réservée au code admin : le code stagiaire permet seulement de consulter la page ' . $this->config()['name'] . '.'
        );
    }

    /** Auteur d'un geste manuel : le code admin est partagé, on garde le compte connecté s'il y en a un. */
    private function adminContext(Request $request): array
    {
        $role = session('is_admin') ? 'admin' : 'stagiaire';
        $user = $request->user();

        return [
            'admin_user_id' => $user?->id,
            'admin_label'   => Str::limit($user ? "{$user->name} (#{$user->id}, code {$role})" : "Code {$role} (sans compte connecté)", 120, ''),
        ];
    }

    private function activeGrantSub(MarketplaceProduct $product): Builder
    {
        return DB::table('admin_grants')
            ->whereColumn('admin_grants.user_id', 'users.id')
            ->where('grantable_type', MarketplaceProduct::class)
            ->where('grantable_id', $product->id)
            ->whereNull('revoked_at')
            ->whereNull('deleted_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    private function players(Request $request, MarketplaceProduct $product, ?User $selected)
    {
        $pid  = $product->id;
        $slug = $this->game()->slug();

        $progress = fn () => DB::table('game_progress')->whereColumn('game_progress.user_id', 'users.id')->where('product_id', $pid);
        $credits  = fn () => DB::table('game_credits')->whereColumn('game_credits.user_id', 'users.id')->where('game_slug', $slug);
        $ledger   = fn () => DB::table('game_credit_ledger')->whereColumn('game_credit_ledger.user_id', 'users.id')->where('game_slug', $slug);
        $purchase = fn () => DB::table('marketplace_purchases')->whereColumn('marketplace_purchases.user_id', 'users.id')->where('product_id', $pid);

        $latest = DB::connection()->getDriverName() === 'sqlite' ? 'MAX' : 'GREATEST';

        $query = User::query()
            ->select('users.id', 'users.name', 'users.email')
            ->selectSub($progress()->select('data')->limit(1), 'ak_progress')
            ->selectSub($progress()->select('updated_at')->limit(1), 'ak_progress_at')
            ->selectSub($progress()->select('free_hint_used_at')->limit(1), 'ak_free_hint_at')
            ->selectSub($credits()->where('kind', StoryGameService::KIND_HINTS)->select('balance')->limit(1), 'ak_hints')
            ->selectSub($ledger()->selectRaw('MAX(created_at)'), 'ak_ledger_at')
            ->selectSub($purchase()->where('status', 'paid')->select('paid_at')->limit(1), 'ak_paid_at')
            ->selectSub($this->activeGrantSub($product)->selectRaw('MAX(granted_at)'), 'ak_granted_at')
            ->where(fn ($q) => $q
                ->whereExists($progress())
                ->orWhereExists($credits())
                ->orWhereExists($ledger())
                ->orWhereExists($purchase())
                ->orWhereExists(DB::table('admin_grants')->whereColumn('admin_grants.user_id', 'users.id')
                    ->where('grantable_type', MarketplaceProduct::class)->where('grantable_id', $pid)))
            ->when($selected, fn ($q) => $q->where('users.id', $selected->id))
            ->when(trim((string) $request->query('q')) !== '', function ($q) use ($request) {
                $term = '%' . trim((string) $request->query('q')) . '%';
                $q->where(fn ($w) => $w->where('users.name', 'like', $term)->orWhere('users.email', 'like', $term));
            })
            ->orderByRaw("{$latest}(COALESCE(ak_progress_at, ''), COALESCE(ak_ledger_at, ''), COALESCE(ak_paid_at, ''), COALESCE(ak_granted_at, '')) DESC")
            ->orderByDesc('users.id');

        $players = $query->paginate(25, ['*'], 'page')->withQueryString();

        $players->getCollection()->transform(function ($row) {
            $data = json_decode((string) $row->ak_progress, true) ?: [];
            $dates = array_filter([$row->ak_progress_at, $row->ak_ledger_at, $row->ak_paid_at, $row->ak_granted_at]);

            $row->unlocked_via  = $row->ak_paid_at ? 'purchase' : ($row->ak_granted_at ? 'grant' : null);
            $row->hints         = (int) $row->ak_hints;
            $row->free_hint_at  = $row->ak_free_hint_at ? Carbon::parse($row->ak_free_hint_at) : null;
            $row->max           = $data['max'] ?? null;
            $row->fins          = array_values($data['fins'] ?? []);
            $row->last_activity = $dates ? Carbon::parse(max($dates)) : null;

            return $row;
        });

        return $players;
    }

    private function ledger(Request $request, ?User $selected)
    {
        $reasons = ['purchase', 'use', 'admin'];

        return GameCreditLedger::query()
            ->with(['user:id,name,email', 'adminUser:id,name'])
            ->where('game_slug', $this->game()->slug())
            ->when($selected, fn ($q) => $q->where('user_id', $selected->id))
            ->when(in_array($request->query('reason'), $reasons, true), fn ($q) => $q->where('reason', $request->query('reason')))
            ->when(trim((string) $request->query('ledger_q')) !== '', function ($q) use ($request) {
                $term = '%' . trim((string) $request->query('ledger_q')) . '%';
                $q->where(fn ($w) => $w
                    ->whereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term))
                    ->orWhere('payment_reference', 'like', $term));
            })
            ->orderByDesc('id')
            ->paginate(30, ['*'], 'ledger_page')
            ->withQueryString();
    }

    /** ?navigateur=facebook|…|inconnu (événements sans catégorie) ; autre valeur : aucun filtre. */
    private function browserFilter(Request $request): ?string
    {
        $value = (string) $request->query('navigateur');

        return in_array($value, [...StoryGameService::BROWSERS, 'inconnu'], true) ? $value : null;
    }

    private function stats(MarketplaceProduct $product, ?string $browser = null): array
    {
        $pid  = $product->id;
        $cfg  = $this->config();
        $game = $this->game();

        // Point le plus loin atteint par joueur connecté (les invités ne sont pas enregistrés)
        $maxRanks = GameProgress::where('product_id', $pid)->pluck('data')
            ->map(fn ($d) => is_string($d) ? json_decode($d, true) : (array) $d)
            ->map(fn ($d) => isset($d['max']) ? $game->rank((string) $d['max']) : null)
            ->filter(fn ($r) => $r !== null);

        $finsCounts = GameProgress::where('product_id', $pid)->pluck('data')
            ->map(fn ($d) => is_string($d) ? json_decode($d, true) : (array) $d)
            ->flatMap(fn ($d) => $d['fins'] ?? [])
            ->countBy();

        $paidUserIds = DB::table('marketplace_purchases')->where('product_id', $pid)->where('status', 'paid')->pluck('user_id');
        $grantUserIds = AdminGrant::where('grantable_type', MarketplaceProduct::class)->where('grantable_id', $pid)->active()->pluck('user_id');

        // Cadenas : a tenté d'acheter la suite en étant connecté, a l'accès, ou a atteint le
        // point du cadenas (lock_threshold)
        $lockRank = $game->rank($cfg['lock_threshold']);
        $beyondLock = GameProgress::where('product_id', $pid)->get(['user_id', 'data'])
            ->filter(function ($p) use ($game, $lockRank) {
                $d = (array) $p->data;
                return isset($d['max']) && $game->rank((string) $d['max']) >= $lockRank;
            })->pluck('user_id');
        $lockUserIds = DB::table('marketplace_purchases')->where('product_id', $pid)->pluck('user_id')
            ->concat($grantUserIds)->concat($beyondLock)->unique();

        $funnel = [];
        foreach ($cfg['funnel_saves'] as $save => $label) {
            $rank = $game->rank((string) $save);
            if ((string) $save === $cfg['lock_before']) {
                $funnel[] = ['label' => '🔒 Cadenas', 'count' => $lockUserIds->count(), 'lock' => true];
            }
            $funnel[] = [
                'label' => $label,
                'count' => $maxRanks->filter(fn ($r) => $r >= $rank)->count(),
                'lock'  => false,
            ];
        }

        // Encaissé via Paystack : achats marketplace payés + packs d'indices
        $paidUnlocks = fn () => DB::table('marketplace_purchases')->where('product_id', $pid)->where('status', 'paid');
        $packs       = fn () => GameCreditLedger::where('game_slug', $game->slug())->where('reason', 'purchase');
        // Encaissé hors Paystack (WhatsApp…) : montant saisi sur les déblocages offerts encore
        // actifs et sur les ajouts d'indices manuels
        $manualUnlocks = fn () => AdminGrant::where('grantable_type', MarketplaceProduct::class)->where('grantable_id', $pid)->active();
        $manualHints   = fn () => GameCreditLedger::where('game_slug', $game->slug())->where('reason', 'admin');

        $revenue = function (?Carbon $since = null) use ($paidUnlocks, $packs, $manualUnlocks, $manualHints) {
            $paystack = (int) $paidUnlocks()->when($since, fn ($q) => $q->where('paid_at', '>=', $since))->sum('amount')
                + (int) $packs()->when($since, fn ($q) => $q->where('created_at', '>=', $since))->sum('amount');
            $manual = (int) $manualUnlocks()->when($since, fn ($q) => $q->where('granted_at', '>=', $since))->sum('amount_received')
                + (int) $manualHints()->when($since, fn ($q) => $q->where('created_at', '>=', $since))->sum('amount');

            return ['paystack' => $paystack, 'manual' => $manual, 'total' => $paystack + $manual];
        };

        $period = function (int $days) use ($paidUnlocks, $packs, $revenue) {
            $since = now()->subDays($days);

            return [
                'unlocks' => $paidUnlocks()->where('paid_at', '>=', $since)->count(),
                'packs'   => $packs()->where('created_at', '>=', $since)->count(),
                'revenue' => $revenue($since),
            ];
        };

        $unlocksPaid = $paidUserIds->unique()->count();

        // Sessions anonymes (événements du jeu, invités et connectés) : total, 24 h, 7 jours
        $sessions = [
            'all' => $this->sessionFunnel(null, $paidUnlocks, $packs, $browser),
            '24h' => $this->sessionFunnel(now()->subDay(), $paidUnlocks, $packs, $browser),
            '7d'  => $this->sessionFunnel(now()->subDays(7), $paidUnlocks, $packs, $browser),
        ];
        $sessionLock = collect($sessions['all']['steps'])->firstWhere('key', 'lock')['count'];

        return [
            'players'           => GameProgress::where('product_id', $pid)->count(),
            'funnel'            => $funnel,
            'sessions'          => $sessions,
            'sessions_since'    => $game->events()->where('type', 'page_open')->min('created_at'),
            'fins'              => collect($cfg['fins'])->map(fn ($label, $key) => (int) ($finsCounts[$key] ?? 0))->all(),
            'lock'              => $lockUserIds->count(),
            'unlocks_paid'      => $unlocksPaid,
            'unlocks_grant'     => $grantUserIds->diff($paidUserIds)->unique()->count(),
            'conversion'        => $lockUserIds->count() > 0 ? round($unlocksPaid * 100 / $lockUserIds->count(), 1) : null,
            // Déblocages payés ÷ sessions arrivées au cadenas (invités et connectés)
            'conversion_global' => $sessionLock > 0 ? round($unlocksPaid * 100 / $sessionLock, 1) : null,
            'packs_sold'        => $packs()->count(),
            'unlock_revenue'    => (int) $paidUnlocks()->sum('amount'),
            'packs_revenue'     => (int) $packs()->sum('amount'),
            'revenue'           => $revenue(),
            'hints_admin'       => (int) $manualHints()->sum('delta'),
            'hints_used'        => GameCreditLedger::where('game_slug', $game->slug())->where('reason', 'use')->count(),
            'last7'             => $period(7),
            'last30'            => $period(30),
        ];
    }

    /**
     * Étapes de l'entonnoir des sessions : nombre de sessions distinctes ayant au moins un des
     * événements de l'étape (depuis $since), % par rapport à l'étape précédente.
     * « Payé » = déblocages payés + packs d'indices vendus sur la période (paiements, pas sessions).
     */
    private function sessionFunnel(?Carbon $since, \Closure $paidUnlocks, \Closure $packs, ?string $browser = null): array
    {
        $stepsDef = [
            ['page',    'Page ouverte',                  ['page_open']],
            ['loaded',  'Jeu chargé',                    $this->config()['loaded_types']],
            ['start',   'Nouvelle partie (ou Continuer)', ['new_game', 'continue']],
            ['ch1',     'Chapitre 1',                    ['ch1_reached']],
            ['ch2',     'Chapitre 2',                    ['ch2_reached']],
            ['lock',    '🔒 Cadenas affiché',            ['paywall_open', 'lock_reached']],
            ['buy',     'Clic « acheter »',              ['buy_click_full', 'buy_click_hints5']],
        ];

        $sessions = fn (array $types) => $this->game()->events()
            ->whereIn('type', $types)
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->when($browser, fn ($q) => $browser === 'inconnu' ? $q->whereNull('browser') : $q->where('browser', $browser))
            ->distinct()
            ->count('session_hash');

        $steps = [];
        foreach ($stepsDef as [$key, $label, $types]) {
            $steps[] = ['key' => $key, 'label' => $label, 'count' => $sessions($types)];
        }
        // Paiements : non rattachés à un navigateur, donc sans valeur quand un filtre est actif
        $steps[] = [
            'key'   => 'paid',
            'label' => 'Payé (déblocages + packs)',
            'count' => $browser ? null : $paidUnlocks()->when($since, fn ($q) => $q->where('paid_at', '>=', $since))->count()
                + $packs()->when($since, fn ($q) => $q->where('created_at', '>=', $since))->count(),
        ];

        foreach ($steps as $i => $step) {
            $previous = $i > 0 ? $steps[$i - 1]['count'] : null;
            $steps[$i]['pct'] = $previous && $step['count'] !== null ? round($step['count'] * 100 / $previous, 1) : null;
        }

        $details = [];
        foreach ($this->config()['details'] as [$key, , $types]) {
            $details[$key] = $sessions($types);
        }

        return ['steps' => $steps, 'details' => $details];
    }

    /**
     * Entonnoir par catégorie de navigateur, sur 7 jours : pour voir si le navigateur intégré
     * de Facebook (pub) décroche plus que les autres. Sessions distinctes par étape.
     */
    private function browserComparison(): array
    {
        $since  = now()->subDays(7);
        $stages = collect($this->config()['browser_stages'])->mapWithKeys(fn ($s) => [$s[0] => $s[2]])->all();

        $counts = [];
        foreach ($stages as $stage => $types) {
            $counts[$stage] = $this->game()->events()
                ->whereIn('type', $types)
                ->where('created_at', '>=', $since)
                ->selectRaw("COALESCE(browser, 'inconnu') as b, COUNT(DISTINCT session_hash) as n")
                ->groupBy('b')
                ->pluck('n', 'b');
        }

        $rows = [];
        foreach ([...StoryGameService::BROWSERS, 'inconnu'] as $browser) {
            $row = ['browser' => $browser];
            foreach (array_keys($stages) as $stage) {
                $row[$stage] = (int) ($counts[$stage][$browser] ?? 0);
            }
            if (array_sum(array_diff_key($row, ['browser' => 1])) === 0) {
                continue;
            }
            $ratio = fn (int $a, int $b) => $b > 0 ? round($a * 100 / $b, 1) : null;
            $row['loaded_pct'] = $ratio($row['loaded'], $row['page']);   // restés pendant le chargement
            $row['lock_pct']   = $ratio($row['lock'], $row['page']);     // arrivés au cadenas
            $rows[] = $row;
        }

        return $rows;
    }
}
