<?php

namespace App\Services\Games;

use App\Models\GameCredit;
use App\Models\GameCreditLedger;
use App\Models\GameProgress;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Base commune des jeux à histoire servis depuis resources/games (Akwaba, Le Pacte) :
 * - début gratuit, suite débloquée par l'achat du produit marketplace du jeu (slug) ou une
 *   attribution admin (admin_grants) ;
 * - indices payants : solde game_credits (kind « hints »), mouvements dans game_credit_ledger ;
 * - un indice gratuit par joueur, pour toujours : game_progress.free_hint_used_at ;
 * - progression : game_progress (product_id du produit du jeu), data propre à chaque jeu.
 *
 * Chaque jeu définit la constante SLUG et la forme de sa progression vide.
 */
abstract class StoryGameService
{
    public const KIND_HINTS = 'hints';

    public const HINTS_PACK_SIZE = 5;
    public const HINTS_PACK_PRICE = 500; // XOF

    /** Catégories de navigateur (aucune autre information du User-Agent n'est gardée). */
    public const BROWSERS = ['facebook', 'instagram', 'whatsapp', 'chrome', 'safari', 'autre'];

    /** Slug du produit marketplace, aussi game_slug des crédits. */
    public function slug(): string
    {
        return static::SLUG;
    }

    /** Contenu de game_progress.data à la création de la ligne. */
    abstract protected function emptyProgress(): array;

    public function product(): ?MarketplaceProduct
    {
        return MarketplaceProduct::where('slug', $this->slug())->where('type', 'game')->first();
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
            ->where('game_slug', $this->slug())
            ->where('kind', self::KIND_HINTS)
            ->value('balance');
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

    protected function ensureProgressRow(User $user, MarketplaceProduct $product): void
    {
        try {
            GameProgress::firstOrCreate(
                ['user_id' => $user->id, 'product_id' => $product->id],
                ['data' => $this->emptyProgress()]
            );
        } catch (UniqueConstraintViolationException) {
            // Créée en même temps par une autre requête du joueur : la ligne existe, on continue
        }
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
                ->where('game_slug', $this->slug())
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
                'game_slug' => $this->slug(),
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
                'game_slug'         => $this->slug(),
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
                'game_slug'  => $this->slug(),
                'kind'       => self::KIND_HINTS,
                'balance'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('game_credits')
                ->where('user_id', $user->id)
                ->where('game_slug', $this->slug())
                ->where('kind', self::KIND_HINTS)
                ->update([
                    'balance'    => DB::raw('balance + ' . (int) $count),
                    'updated_at' => now(),
                ]);

            return $this->hints($user);
        });
    }

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
}
