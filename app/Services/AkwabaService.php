<?php

namespace App\Services;

use App\Models\GameCredit;
use App\Models\GameCreditLedger;
use App\Models\GameProgress;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Akwaba : le mystère de la lagune Ébrié (resources/games/akwaba.html).
 * - Prologue et chapitres 1-2 gratuits ; 3 à 7 et les deux fins débloqués par l'achat
 *   du produit marketplace « akwaba » ou une attribution admin (admin_grants).
 * - Indices payants : solde game_credits (kind « hints »), chaque mouvement dans game_credit_ledger.
 * - Progression : game_progress (product_id du produit akwaba), data = {save, fins, max}.
 */
class AkwabaService
{
    public const SLUG = 'akwaba';
    public const KIND_HINTS = 'hints';

    public const HINTS_PACK_SIZE = 5;
    public const HINTS_PACK_PRICE = 500; // XOF

    // Ordre des sauvegardes du jeu : P (prologue) puis chapitres 1 à 7
    public const SAVES = ['P', '1', '2', '3', '4', '5', '6', '7'];
    public const FREE_SAVES = ['P', '1', '2'];
    public const FINS = ['oubli', 'gardien'];

    public function product(): ?MarketplaceProduct
    {
        return MarketplaceProduct::where('slug', self::SLUG)->where('type', 'game')->first();
    }

    public function isUnlocked(?User $user, ?MarketplaceProduct $product = null): bool
    {
        $product ??= $this->product();

        if (!$user || !$product) {
            return false;
        }

        return $user->purchasedProducts()
                ->where('marketplace_products.id', $product->id)
                ->wherePivot('status', 'paid')
                ->exists()
            || $product->isGrantedTo($user);
    }

    public function hints(User $user): int
    {
        return (int) GameCredit::where('user_id', $user->id)
            ->where('game_slug', self::SLUG)
            ->where('kind', self::KIND_HINTS)
            ->value('balance');
    }

    /** Progression telle qu'envoyée au jeu : {save, fins}. */
    public function progress(User $user, ?MarketplaceProduct $product = null): array
    {
        $product ??= $this->product();

        $data = $product
            ? (GameProgress::where('user_id', $user->id)->where('product_id', $product->id)->value('data') ?? [])
            : [];

        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }

        return [
            'save' => $data['save'] ?? null,
            'fins' => array_values($data['fins'] ?? []),
        ];
    }

    /**
     * Vue admin d'un joueur : origine du déblocage, solde, chapitres, fins.
     *
     * @return array{unlocked: bool, via: ?string, hints: int, save: ?string, max: ?string, fins: array, updated_at: mixed}
     */
    public function playerSummary(User $user): array
    {
        $product = $this->product();

        $paid = $product && $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists();
        $granted = $product && $product->isGrantedTo($user);

        $progress = $product
            ? GameProgress::where('user_id', $user->id)->where('product_id', $product->id)->first()
            : null;
        $data = (array) ($progress?->data ?? []);

        return [
            'unlocked'   => $paid || $granted,
            'via'        => $paid ? 'purchase' : ($granted ? 'grant' : null),
            'hints'      => $this->hints($user),
            'save'       => $data['save'] ?? null,
            'max'        => $data['max'] ?? null,
            'fins'       => array_values($data['fins'] ?? []),
            'updated_at' => $progress?->updated_at,
        ];
    }

    /** Message « state » du contrat postMessage. */
    public function state(?User $user): array
    {
        if (!$user) {
            return [
                'logged'   => false,
                'unlocked' => false,
                'hints'    => 0,
                'freeUsed' => false,
                'progress' => ['save' => null, 'fins' => []],
            ];
        }

        $product = $this->product();

        return [
            'logged'   => true,
            'unlocked' => $this->isUnlocked($user, $product),
            'hints'    => $this->hints($user),
            'freeUsed' => $this->freeHintUsed($user, $product),
            'progress' => $this->progress($user, $product),
        ];
    }

    /** Indice gratuit déjà utilisé ? (un seul par joueur, pour toujours) */
    public function freeHintUsed(User $user, ?MarketplaceProduct $product = null): bool
    {
        $product ??= $this->product();

        return $product !== null && GameProgress::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->whereNotNull('free_hint_used_at')
            ->exists();
    }

    /**
     * Marque l'indice gratuit comme utilisé. Idempotent : seule la première date est gardée
     * (UPDATE conditionnel, sans risque même avec deux appels simultanés).
     *
     * @return bool true si l'indice vient d'être marqué, false s'il l'était déjà
     */
    public function markFreeHintUsed(User $user, MarketplaceProduct $product): bool
    {
        $this->ensureProgressRow($user, $product);

        return GameProgress::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->whereNull('free_hint_used_at')
            ->update(['free_hint_used_at' => now(), 'updated_at' => now()]) === 1;
    }

    private function ensureProgressRow(User $user, MarketplaceProduct $product): void
    {
        try {
            GameProgress::firstOrCreate(
                ['user_id' => $user->id, 'product_id' => $product->id],
                ['data' => ['save' => null, 'fins' => [], 'max' => null]]
            );
        } catch (UniqueConstraintViolationException) {
            // Créée en même temps par une autre requête du joueur : la ligne existe, on continue
        }
    }

    /**
     * Enregistre la progression : le chapitre en cours suit le jeu (il peut revenir en
     * arrière), les fins sont fusionnées par union et ne disparaissent jamais, « max »
     * garde le chapitre le plus loin atteint (statistiques admin).
     */
    public function saveProgress(User $user, MarketplaceProduct $product, ?string $save, array $fins): array
    {
        $this->ensureProgressRow($user, $product);

        return DB::transaction(function () use ($user, $product, $save, $fins) {
            $progress = GameProgress::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $data = (array) $progress->data;

            $mergedFins = array_values(array_intersect(
                self::FINS,
                array_unique(array_merge($data['fins'] ?? [], $fins))
            ));

            $max = $data['max'] ?? null;
            if ($save !== null && ($max === null || $this->rank($save) > $this->rank($max))) {
                $max = $save;
            }

            $progress->data = ['save' => $save, 'fins' => $mergedFins, 'max' => $max];
            $progress->save();

            return ['save' => $save, 'fins' => $mergedFins];
        });
    }

    /**
     * Consomme un indice. UPDATE conditionnel (balance > 0) : atomique même sous SQLite,
     * où lockForUpdate() n'a pas d'effet ; le solde ne peut jamais passer sous zéro.
     *
     * @return array{ok: bool, hints: int}
     */
    public function useHint(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $updated = DB::table('game_credits')
                ->where('user_id', $user->id)
                ->where('game_slug', self::SLUG)
                ->where('kind', self::KIND_HINTS)
                ->where('balance', '>', 0)
                ->update([
                    'balance'    => DB::raw('balance - 1'),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                return ['ok' => false, 'hints' => $this->hints($user)];
            }

            GameCreditLedger::create([
                'user_id'   => $user->id,
                'game_slug' => self::SLUG,
                'kind'      => self::KIND_HINTS,
                'delta'     => -1,
                'reason'    => 'use',
            ]);

            return ['ok' => true, 'hints' => $this->hints($user)];
        });
    }

    /**
     * Ajoute des indices (achat ou geste admin). Une référence de paiement déjà enregistrée
     * lève UniqueConstraintViolationException et la transaction n'ajoute rien.
     *
     * @param array{admin_user_id?: ?int, admin_label?: ?string} $admin auteur d'un mouvement manuel
     */
    public function addHints(User $user, int $count, string $reason, ?string $paymentReference = null, ?int $amount = null, ?string $note = null, array $admin = []): int
    {
        return DB::transaction(function () use ($user, $count, $reason, $paymentReference, $amount, $note, $admin) {
            GameCreditLedger::create([
                'user_id'           => $user->id,
                'game_slug'         => self::SLUG,
                'kind'              => self::KIND_HINTS,
                'delta'             => $count,
                'reason'            => $reason,
                'payment_reference' => $paymentReference,
                'amount'            => $amount,
                'note'              => $note,
                'admin_user_id'     => $admin['admin_user_id'] ?? null,
                'admin_label'       => $admin['admin_label'] ?? null,
            ]);

            DB::table('game_credits')->insertOrIgnore([
                'user_id'    => $user->id,
                'game_slug'  => self::SLUG,
                'kind'       => self::KIND_HINTS,
                'balance'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('game_credits')
                ->where('user_id', $user->id)
                ->where('game_slug', self::SLUG)
                ->where('kind', self::KIND_HINTS)
                ->update([
                    'balance'    => DB::raw('balance + ' . (int) $count),
                    'updated_at' => now(),
                ]);

            return $this->hints($user);
        });
    }

    /**
     * Événements anonymes de l'entonnoir (liste blanche stricte), invités et connectés :
     *  - page hôte : page_open (ouverture), page_loaded (jeu chargé, message ready),
     *    ch1_reached / ch2_reached / lock_reached (progress save 1 / 2 / 3) ;
     *  - jeu (message event) : new_game, continue, free_hint, shop_open, paywall_open,
     *    buy_click_full, buy_click_hints5.
     * play_start : ancien nom de page_loaded (avant le 03/11/2026), compté avec lui.
     */
    public const EVENTS = [
        'page_open', 'page_loaded', 'play_start',
        'new_game', 'continue', 'ch1_reached', 'ch2_reached', 'lock_reached',
        'free_hint', 'shop_open', 'paywall_open', 'buy_click_full', 'buy_click_hints5',
        'wait_rest',     // le joueur a dû attendre la fin du chargement des images
        'open_external', // clic « Ouvrir dans Chrome » du bandeau des navigateurs intégrés
    ];

    /** Catégories de navigateur (aucune autre information du User-Agent n'est gardée). */
    public const BROWSERS = ['facebook', 'instagram', 'whatsapp', 'chrome', 'safari', 'autre'];

    public const MAX_ERRORS_PER_SESSION = 20;

    /**
     * Catégorie de navigateur d'après le User-Agent. Les navigateurs intégrés passent avant
     * les navigateurs classiques : celui de Facebook (FBAN, FBAV, FB_IAB) contient aussi
     * « Chrome » ou « Safari ».
     */
    public static function browserFamily(?string $userAgent): string
    {
        $ua = (string) $userAgent;

        return match (true) {
            (bool) preg_match('/FBAN|FBAV|FB_IAB/', $ua)                    => 'facebook',
            str_contains($ua, 'Instagram')                                   => 'instagram',
            str_contains($ua, 'WhatsApp')                                    => 'whatsapp',
            (bool) preg_match('/Edg\/|EdgA\/|EdgiOS|OPR\/|SamsungBrowser|Firefox|FxiOS/', $ua) => 'autre',
            (bool) preg_match('/Chrome\/|CriOS/', $ua)                      => 'chrome',
            (bool) preg_match('/Safari\//', $ua)                            => 'safari',
            default                                                          => 'autre',
        };
    }

    /**
     * Navigateur intégré à une application (Facebook, Instagram, WhatsApp, ou toute autre webview) ?
     * Il charge souvent mal un gros jeu : la page propose alors de l'ouvrir dans le navigateur.
     *
     * @return array{in_app: bool, platform: string} platform : android | ios | other
     */
    public static function inAppBrowser(?string $userAgent): array
    {
        $ua = (string) $userAgent;

        $platform = match (true) {
            str_contains($ua, 'Android')                   => 'android',
            (bool) preg_match('/iPhone|iPad|iPod/', $ua)   => 'ios',
            default                                        => 'other',
        };

        $inApp = in_array(self::browserFamily($ua), ['facebook', 'instagram', 'whatsapp'], true)
            // Webview Android générique (« ; wv) ») ; sur iPhone, une webview n'annonce pas Safari
            || ($platform === 'android' && str_contains($ua, '; wv)'))
            || ($platform === 'ios' && !str_contains($ua, 'Safari/'));

        return ['in_app' => $inApp, 'platform' => $platform];
    }

    private function sessionHash(string $sessionId): string
    {
        return hash_hmac('sha256', $sessionId, (string) config('app.key'));
    }

    /**
     * Compteur anonyme : au plus une ligne par session et par type (index unique). L'id de
     * session n'est jamais stocké, seulement son HMAC avec la clé de l'application ; ni IP ni
     * compte ; du navigateur, seulement sa catégorie.
     */
    public function recordEvent(string $type, string $sessionId, ?string $userAgent = null): void
    {
        if (!in_array($type, self::EVENTS, true) || $sessionId === '') {
            return;
        }

        DB::table('akwaba_events')->insertOrIgnore([
            'type'         => $type,
            'session_hash' => $this->sessionHash($sessionId),
            'browser'      => self::browserFamily($userAgent),
            'created_at'   => now(),
        ]);
    }

    /**
     * Erreur JavaScript du jeu : message tronqué à 200 caractères, position, catégorie de
     * navigateur, date. Au plus MAX_ERRORS_PER_SESSION par session (compteur en cache, la
     * session n'est pas stockée avec l'erreur).
     *
     * @return bool false si la limite de la session est atteinte
     */
    public function recordError(string $msg, ?int $line, ?int $col, string $sessionId, ?string $userAgent): bool
    {
        if ($sessionId === '') {
            return false;
        }

        $key = 'akwaba.errors.' . $this->sessionHash($sessionId);
        Cache::add($key, 0, now()->addDay());
        if (Cache::increment($key) > self::MAX_ERRORS_PER_SESSION) {
            return false;
        }

        DB::table('akwaba_errors')->insert([
            'msg'        => Str::limit(trim(preg_replace('/\s+/', ' ', $msg)), 200, ''),
            'line'       => $line,
            'col'        => $col,
            'browser'    => self::browserFamily($userAgent),
            'created_at' => now(),
        ]);

        return true;
    }

    public function rank(string $save): int
    {
        return (int) array_search($save, self::SAVES, true);
    }
}
