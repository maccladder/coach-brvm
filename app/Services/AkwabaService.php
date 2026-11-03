<?php

namespace App\Services;

use App\Models\GameCredit;
use App\Models\GameCreditLedger;
use App\Models\GameProgress;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

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
                'progress' => ['save' => null, 'fins' => []],
            ];
        }

        $product = $this->product();

        return [
            'logged'   => true,
            'unlocked' => $this->isUnlocked($user, $product),
            'hints'    => $this->hints($user),
            'progress' => $this->progress($user, $product),
        ];
    }

    /**
     * Enregistre la progression : le chapitre en cours suit le jeu (il peut revenir en
     * arrière), les fins sont fusionnées par union et ne disparaissent jamais, « max »
     * garde le chapitre le plus loin atteint (statistiques admin).
     */
    public function saveProgress(User $user, MarketplaceProduct $product, ?string $save, array $fins): array
    {
        try {
            GameProgress::firstOrCreate(
                ['user_id' => $user->id, 'product_id' => $product->id],
                ['data' => ['save' => null, 'fins' => [], 'max' => null]]
            );
        } catch (UniqueConstraintViolationException) {
            // Première sauvegarde envoyée deux fois en même temps : la ligne existe, on continue
        }

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

    public const GUEST_EVENTS = ['play_start', 'lock_reached'];

    /**
     * Compteur anonyme d'invités : au plus une ligne par session et par type (index unique).
     * L'id de session n'est jamais stocké, seulement son HMAC avec la clé de l'application.
     */
    public function recordGuestEvent(string $type, string $sessionId): void
    {
        if (!in_array($type, self::GUEST_EVENTS, true) || $sessionId === '') {
            return;
        }

        DB::table('akwaba_events')->insertOrIgnore([
            'type'         => $type,
            'session_hash' => hash_hmac('sha256', $sessionId, (string) config('app.key')),
            'created_at'   => now(),
        ]);
    }

    public function rank(string $save): int
    {
        return (int) array_search($save, self::SAVES, true);
    }
}
