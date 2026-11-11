<?php

namespace App\Services;

use App\Models\GameProgress;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Services\Games\StoryGameService;
use Illuminate\Support\Facades\DB;

/**
 * Le Pacte avec les caïmans de Yamoussoukro (resources/games/le-pacte/index.html + img/).
 * - Prologue et chapitres 1-2 gratuits ; 3 à 7 et les 5 fins débloqués par l'achat du produit
 *   marketplace « le-pacte » ou une attribution admin (admin_grants).
 * - Indices : 1 gratuit à vie par compte (game_progress.free_hint_used_at), consommé avant le
 *   solde payé ; puis packs de 5 (game_credits, kind « hints »). Le jeu ne voit qu'un total.
 * - Progression : game_progress (product_id du produit le-pacte), data =
 *   {save: {point, etat} | null, max, fins: [clé…], fins_detail: {clé: {choix, at}}}.
 *   « etat » est l'objet du jeu, stocké tel quel (texte JSON).
 * Déblocage, indices et crédits : StoryGameService, commun aux jeux à histoire.
 */
class PacteService extends StoryGameService
{
    public const SLUG = 'le-pacte';

    // Points de reprise du jeu (POINTS dans index.html), dans l'ordre de l'histoire.
    // finGratuite : écran « Fin de la partie gratuite », point de retour du paiement.
    public const POINTS = ['gare', 'villa', 'funerailles', 'finGratuite', 'chap3', 'chap4', 'chap5', 'chap6', 'chap7'];
    public const FREE_POINTS = ['gare', 'villa', 'funerailles', 'finGratuite'];
    public const FINS = ['pacte', 'justice', 'argent', 'gardien', 'sacrifice'];

    /**
     * Événements anonymes de l'entonnoir (liste blanche stricte), invités et connectés,
     * tous envoyés par la page hôte (table game_events) :
     *  - page : page_open, page_loaded (iframe chargée), open_external (bandeau navigateur intégré) ;
     *  - pont PacteHost : ch1_reached / ch2_reached / lock_reached (sauvegarde villa / funerailles /
     *    finGratuite), buy_click_full (debloquer), buy_click_hints5 (acheterIndices), shop_open
     *    (plus d'indice : la boutique s'ouvre), free_hint, lang_fr / lang_en (langue()) ;
     *  - jeu, par PacteHost.evenement() : new_game, continue, paywall_open (écran de fin
     *    gratuite réaffiché à la reprise).
     */
    public const EVENTS = [
        'page_open', 'page_loaded', 'open_external',
        'new_game', 'continue', 'ch1_reached', 'ch2_reached', 'lock_reached', 'paywall_open',
        'free_hint', 'shop_open', 'buy_click_full', 'buy_click_hints5',
        'lang_fr', 'lang_en',
    ];

    /** Taille maximale de l'état sauvegardé (JSON), le jeu en produit 5 à 10 Ko. */
    public const MAX_ETAT_BYTES = 65536;
    /** Taille maximale de la liste des choix envoyée avec une fin. */
    public const MAX_CHOIX_BYTES = 16384;

    protected function emptyProgress(): array
    {
        return ['save' => null, 'max' => null, 'fins' => []];
    }

    private function progressData(User $user, ?MarketplaceProduct $product): array
    {
        if (!$product) {
            return [];
        }

        $data = GameProgress::where('user_id', $user->id)->where('product_id', $product->id)->value('data') ?? [];

        return is_string($data) ? (json_decode($data, true) ?: []) : (array) $data;
    }

    /**
     * État envoyé à la page hôte : connexion, déblocage, indices disponibles (gratuit compris),
     * sauvegarde du joueur.
     */
    public function state(?User $user): array
    {
        if (!$user) {
            return ['logged' => false, 'unlocked' => false, 'hints' => 0, 'freeUsed' => false, 'save' => null];
        }

        $product  = $this->product();
        $freeUsed = $this->freeHintUsed($user, $product);

        return [
            'logged'   => true,
            'unlocked' => $this->isUnlocked($user, $product),
            'hints'    => $this->hints($user) + ($freeUsed ? 0 : 1),
            'freeUsed' => $freeUsed,
            'save'     => $this->savedGame($user, $product),
        ];
    }

    /**
     * Sauvegarde telle que le jeu l'a envoyée : {point, etat}. L'état est gardé en texte JSON et
     * relu en objets, pour que {} et [] restent distincts (un tableau PHP les confondrait).
     */
    public function savedGame(User $user, ?MarketplaceProduct $product = null): ?array
    {
        $save = $this->progressData($user, $product ?? $this->product())['save'] ?? null;

        if (!is_array($save) || !is_string($save['point'] ?? null) || !is_string($save['etat'] ?? null)) {
            return null;
        }

        $etat = json_decode($save['etat']);

        return $etat instanceof \stdClass ? ['point' => $save['point'], 'etat' => $etat] : null;
    }

    /** Point de sauvegarde en cours : data.save = {point, etat}. */
    protected function savePoint(array $data): ?string
    {
        $point = $data['save']['point'] ?? null;

        return is_string($point) ? $point : null;
    }

    public function isFreePoint(string $point): bool
    {
        return in_array($point, self::FREE_POINTS, true);
    }

    /**
     * Enregistre la sauvegarde du jeu (null = effacée, fin de partie). « max » garde le point le
     * plus loin atteint ; les fins ne sont jamais effacées.
     *
     * @param array{point: string, etat: string}|null $save etat : texte JSON de l'objet du jeu
     */
    public function saveGame(User $user, MarketplaceProduct $product, ?array $save): void
    {
        $this->ensureProgressRow($user, $product);

        DB::transaction(function () use ($user, $product, $save) {
            $progress = GameProgress::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $data = (array) $progress->data;
            $max  = $data['max'] ?? null;

            if ($save !== null && ($max === null || $this->rank($save['point']) > $this->rank($max))) {
                $max = $save['point'];
            }

            $progress->data = array_merge($data, ['save' => $save, 'max' => $max]);
            $progress->save();
        });
    }

    /**
     * Fin obtenue, pour les statistiques : liste des fins (union) et, pour chaque fin, les
     * choix de la dernière partie qui l'a atteinte.
     */
    public function recordFin(User $user, MarketplaceProduct $product, string $cle, array $choix): void
    {
        $this->ensureProgressRow($user, $product);

        DB::transaction(function () use ($user, $product, $cle, $choix) {
            $progress = GameProgress::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $data   = (array) $progress->data;
            $fins   = array_values(array_intersect(self::FINS, array_unique([...($data['fins'] ?? []), $cle])));
            $detail = (array) ($data['fins_detail'] ?? []);
            $detail[$cle] = ['choix' => $choix, 'at' => now()->toIso8601String()];

            $progress->data = array_merge($data, ['fins' => $fins, 'fins_detail' => $detail]);
            $progress->save();
        });
    }

    /**
     * Consomme un indice : le gratuit d'abord (une seule fois, pour toujours), puis le solde payé.
     *
     * @return array{ok: bool, hints: int} hints = indices encore disponibles
     */
    public function consumeHint(User $user, MarketplaceProduct $product): array
    {
        if ($this->markFreeHintUsed($user, $product)) {
            return ['ok' => true, 'hints' => $this->hints($user)];
        }

        // Gratuit déjà utilisé : le total disponible est le solde payé
        return $this->useHint($user);
    }

    public function rank(string $point): int
    {
        return (int) array_search($point, self::POINTS, true);
    }
}
