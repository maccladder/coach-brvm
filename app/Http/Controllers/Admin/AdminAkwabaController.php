<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAdminGrantNotificationJob;
use App\Models\AdminGrant;
use App\Models\GameCreditLedger;
use App\Models\GameProgress;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Services\AkwabaService;
use App\Services\Payments\PaystackAkwabaHintsService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Admin d'Akwaba (/admin/akwaba, middleware admin.code) : joueurs, entonnoir, ventes,
 * historique des indices, et gestes manuels (déblocage, indices, rattrapage d'un pack).
 */
class AdminAkwabaController extends Controller
{
    public function __construct(private AkwabaService $akwaba) {}

    /**
     * GET /admin/akwaba?q=&user=&ledger_q=&reason=
     */
    public function index(Request $request)
    {
        $product = $this->akwaba->product();
        abort_unless($product, 404, 'Produit akwaba introuvable (migration non lancée ?).');

        $selected = $request->filled('user') ? User::find((int) $request->query('user')) : null;

        return view('admin.akwaba', [
            'product'         => $product,
            'stats'           => $this->stats($product),
            'players'         => $this->players($request, $product, $selected),
            'ledger'          => $this->ledger($request, $selected),
            'selected'        => $selected,
            'selectedSummary' => $selected ? $this->akwaba->playerSummary($selected) : null,
        ]);
    }

    /**
     * POST /admin/akwaba/users/{user}/unlock  {reason}
     * Attribution marketplace (admin_grants), comme depuis la page Attributions.
     */
    public function unlock(Request $request, User $user)
    {
        $data    = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        $product = $this->product();
        $admin   = $this->adminContext($request);

        if ($this->akwaba->isUnlocked($user, $product)) {
            return back()->with('error', "{$user->name} a déjà l'histoire complète.");
        }

        $grant = AdminGrant::create([
            'user_id'        => $user->id,
            'grantable_type' => MarketplaceProduct::class,
            'grantable_id'   => $product->id,
            'reason'         => 'Akwaba : ' . $data['reason'] . ' · par ' . $admin['admin_label'],
            'granted_by'     => $admin['admin_user_id'],
            'granted_at'     => now(),
        ]);

        Log::info('Akwaba admin: déblocage attribué', ['grant_id' => $grant->id, 'user_id' => $user->id] + $admin);

        try {
            SendAdminGrantNotificationJob::dispatch($grant);
        } catch (\Throwable $e) {
            Log::error('Akwaba admin: notification d\'attribution', ['error' => $e->getMessage()]);
        }

        return back()->with('success', "Histoire complète débloquée pour {$user->name}.");
    }

    /**
     * POST /admin/akwaba/users/{user}/lock  {reason}
     * Révoque les attributions actives. Un achat Paystack payé ne se retire pas ici.
     */
    public function lock(Request $request, User $user)
    {
        $data    = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        $product = $this->product();
        $admin   = $this->adminContext($request);

        $grants = AdminGrant::where('user_id', $user->id)
            ->where('grantable_type', MarketplaceProduct::class)
            ->where('grantable_id', $product->id)
            ->active()
            ->get();

        if ($grants->isEmpty()) {
            $paid = $this->akwaba->playerSummary($user)['via'] === 'purchase';

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

        Log::info('Akwaba admin: déblocage retiré', ['user_id' => $user->id, 'grants' => $grants->pluck('id')] + $admin);

        $stillPaid = $this->akwaba->isUnlocked($user, $product);

        return back()->with('success', "Attribution retirée pour {$user->name}."
            . ($stillPaid ? ' Il garde l\'accès grâce à son achat Paystack.' : ''));
    }

    /**
     * POST /admin/akwaba/users/{user}/hints  {count, reason}
     */
    public function addHints(Request $request, User $user)
    {
        $data = $request->validate([
            'count'  => ['required', 'integer', 'min:1', 'max:100'],
            'reason' => ['required', 'string', 'max:200'],
        ]);
        $admin = $this->adminContext($request);

        $balance = $this->akwaba->addHints($user, (int) $data['count'], 'admin', null, null, $data['reason'], $admin);

        Log::info('Akwaba admin: indices ajoutés', ['user_id' => $user->id, 'count' => (int) $data['count']] + $admin);

        return back()->with('success', "+{$data['count']} indices pour {$user->name} (solde : {$balance}).");
    }

    /**
     * POST /admin/akwaba/recover  {reference}
     * Rattrapage d'un pack d'indices payé mais non crédité : revérifié chez Paystack, crédité
     * au joueur des métadonnées, une seule fois (payment_reference unique).
     */
    public function recover(Request $request, PaystackAkwabaHintsService $hints)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:120', 'regex:/^AKW-H-[A-Za-z0-9-]+$/'],
        ], [
            'reference.regex' => 'La référence doit commencer par AKW-H- (pack d\'indices Akwaba).',
        ]);
        $reference = $data['reference'];

        $existing = GameCreditLedger::where('payment_reference', $reference)->with('user')->first();
        if ($existing) {
            return back()->with('info', "Référence déjà créditée le {$existing->created_at->format('d/m/Y H:i')} à "
                . ($existing->user?->name ?? 'joueur #' . $existing->user_id) . ' : rien à faire.');
        }

        $outcome = $hints->handle($reference, $this->adminContext($request));

        $credited = GameCreditLedger::where('payment_reference', $reference)->with('user')->first();

        return match ($outcome) {
            PaystackAkwabaHintsService::GRANTED => back()->with('success', "Pack rattrapé : +5 indices pour {$credited?->user?->name}."),
            PaystackAkwabaHintsService::ALREADY => back()->with('info', 'Référence déjà créditée entre-temps (webhook) : rien à faire.'),
            PaystackAkwabaHintsService::MISMATCH => back()->with('error', 'Paystack ne confirme pas ce pack : statut, montant (500 XOF), devise ou métadonnées incorrects. Aucun crédit.'),
            default => back()->with('error', 'Transaction introuvable chez Paystack, ou joueur inconnu. Aucun crédit.'),
        };
    }

    // ─── Données de la page ─────────────────────────────────────────────

    private function product(): MarketplaceProduct
    {
        $product = $this->akwaba->product();
        abort_unless($product, 404);

        return $product;
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
        $pid = $product->id;

        $progress = fn () => DB::table('game_progress')->whereColumn('game_progress.user_id', 'users.id')->where('product_id', $pid);
        $credits  = fn () => DB::table('game_credits')->whereColumn('game_credits.user_id', 'users.id')->where('game_slug', AkwabaService::SLUG);
        $ledger   = fn () => DB::table('game_credit_ledger')->whereColumn('game_credit_ledger.user_id', 'users.id')->where('game_slug', AkwabaService::SLUG);
        $purchase = fn () => DB::table('marketplace_purchases')->whereColumn('marketplace_purchases.user_id', 'users.id')->where('product_id', $pid);

        $latest = DB::connection()->getDriverName() === 'sqlite' ? 'MAX' : 'GREATEST';

        $query = User::query()
            ->select('users.id', 'users.name', 'users.email')
            ->selectSub($progress()->select('data')->limit(1), 'ak_progress')
            ->selectSub($progress()->select('updated_at')->limit(1), 'ak_progress_at')
            ->selectSub($credits()->where('kind', AkwabaService::KIND_HINTS)->select('balance')->limit(1), 'ak_hints')
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
            ->where('game_slug', AkwabaService::SLUG)
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

    private function stats(MarketplaceProduct $product): array
    {
        $pid = $product->id;

        // Chapitre le plus loin atteint par joueur connecté (les invités ne sont pas enregistrés)
        $maxRanks = GameProgress::where('product_id', $pid)->pluck('data')
            ->map(fn ($d) => is_string($d) ? json_decode($d, true) : (array) $d)
            ->map(fn ($d) => isset($d['max']) ? $this->akwaba->rank((string) $d['max']) : null)
            ->filter(fn ($r) => $r !== null);

        $finsCounts = GameProgress::where('product_id', $pid)->pluck('data')
            ->map(fn ($d) => is_string($d) ? json_decode($d, true) : (array) $d)
            ->flatMap(fn ($d) => $d['fins'] ?? [])
            ->countBy();

        $paidUserIds = DB::table('marketplace_purchases')->where('product_id', $pid)->where('status', 'paid')->pluck('user_id');
        $grantUserIds = AdminGrant::where('grantable_type', MarketplaceProduct::class)->where('grantable_id', $pid)->active()->pluck('user_id');

        // Cadenas : a cliqué « Continuer l'aventure » connecté (tentative d'achat), a l'accès,
        // ou a dépassé le chapitre 2
        $beyondLock = GameProgress::where('product_id', $pid)->get(['user_id', 'data'])
            ->filter(function ($p) {
                $d = (array) $p->data;
                return isset($d['max']) && $this->akwaba->rank((string) $d['max']) >= $this->akwaba->rank('3');
            })->pluck('user_id');
        $lockUserIds = DB::table('marketplace_purchases')->where('product_id', $pid)->pluck('user_id')
            ->concat($grantUserIds)->concat($beyondLock)->unique();

        $funnel = [];
        foreach (AkwabaService::SAVES as $save) {
            $rank = $this->akwaba->rank($save);
            if ($save === '3') {
                $funnel[] = ['label' => '🔒 Cadenas', 'count' => $lockUserIds->count(), 'lock' => true];
            }
            $funnel[] = [
                'label' => $save === 'P' ? 'Prologue' : 'Chapitre ' . $save,
                'count' => $maxRanks->filter(fn ($r) => $r >= $rank)->count(),
                'lock'  => false,
            ];
        }

        $unlockRevenue = (int) DB::table('marketplace_purchases')->where('product_id', $pid)->where('status', 'paid')->sum('amount');
        $packs = GameCreditLedger::where('game_slug', AkwabaService::SLUG)->where('reason', 'purchase');

        $period = function (int $days) use ($pid) {
            $since = now()->subDays($days);
            $unlocks = DB::table('marketplace_purchases')->where('product_id', $pid)->where('status', 'paid')->where('paid_at', '>=', $since);
            $packs = GameCreditLedger::where('game_slug', AkwabaService::SLUG)->where('reason', 'purchase')->where('created_at', '>=', $since);

            return [
                'unlocks' => (clone $unlocks)->count(),
                'packs'   => (clone $packs)->count(),
                'revenue' => (int) $unlocks->sum('amount') + (int) $packs->sum('amount'),
            ];
        };

        return [
            'players'        => GameProgress::where('product_id', $pid)->count(),
            'funnel'         => $funnel,
            'fins'           => ['oubli' => (int) ($finsCounts['oubli'] ?? 0), 'gardien' => (int) ($finsCounts['gardien'] ?? 0)],
            'lock'           => $lockUserIds->count(),
            'unlocks_paid'   => $paidUserIds->unique()->count(),
            'unlocks_grant'  => $grantUserIds->diff($paidUserIds)->unique()->count(),
            'conversion'     => $lockUserIds->count() > 0 ? round($paidUserIds->unique()->count() * 100 / $lockUserIds->count(), 1) : null,
            'packs_sold'     => (clone $packs)->count(),
            'unlock_revenue' => $unlockRevenue,
            'packs_revenue'  => (int) (clone $packs)->sum('amount'),
            'revenue'        => $unlockRevenue + (int) (clone $packs)->sum('amount'),
            'hints_admin'    => (int) GameCreditLedger::where('game_slug', AkwabaService::SLUG)->where('reason', 'admin')->sum('delta'),
            'hints_used'     => GameCreditLedger::where('game_slug', AkwabaService::SLUG)->where('reason', 'use')->count(),
            'last7'          => $period(7),
            'last30'         => $period(30),
        ];
    }
}
