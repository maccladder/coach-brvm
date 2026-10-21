<?php

namespace App\Http\Controllers;

use App\Models\GameProgress;
use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Progression de "Le Prince de Yop" (plateformer, 6 niveaux numérotés 0 à 5).
 * Le jeu poste PRINCE_YOP_PROGRESS / _LEVEL_COMPLETE / _GAME_COMPLETE par postMessage ;
 * la page parente (resources/views/prince-de-yop-play.blade.php) les relaie ici, dans l'ordre.
 *
 * Règles anti-triche :
 * - progress : on n'avance que d'un niveau à la fois ; un niveau inférieur ou égal à la
 *   sauvegarde est ignoré (on ne perd jamais la meilleure progression) ; bonus ≤ level
 *   (au plus une bouteille de bissap cachée par quartier).
 * - game_complete : seulement depuis le dernier niveau ; remet la reprise à 0 mais garde
 *   "completed" pour les stats.
 * - level_complete : stats seulement, pour un niveau déjà franchi.
 */
class PrinceDeYopProgressController extends Controller
{
    private const PRODUCT_SLUG = 'le-prince-de-yop';

    private const LAST_LEVEL = 5;
    private const MAX_BONUS = 6;
    private const MAX_LEVEL_TIME = 300; // LVL_TIME du jeu, en secondes
    private const MAX_DEATHS = 1000;

    private const DEFAULTS = [
        'level'              => 0,
        'bonus'              => 0,
        'best_level'         => 0, // meilleur niveau atteint, 6 = jeu fini
        'completed'          => false,
        'completions'        => 0,
        'first_completed_at' => null,
        'best_times'         => [], // index du niveau => meilleur temps (s)
        'total_deaths'       => 0,
    ];

    private function resolveProduct(): ?MarketplaceProduct
    {
        return MarketplaceProduct::where('slug', self::PRODUCT_SLUG)
            ->where('type', 'game')
            ->first();
    }

    private function hasAccess(Request $request, MarketplaceProduct $product): bool
    {
        $user = $request->user();

        return $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists()
            || $product->isGrantedTo($user);
    }

    private function payload(array $data, bool $saved): array
    {
        return [
            'saved'       => $saved,
            'level'       => (int) $data['level'],
            'bonus'       => (int) $data['bonus'],
            'best_level'  => (int) $data['best_level'],
            'completed'   => (bool) $data['completed'],
            'completions' => (int) $data['completions'],
        ];
    }

    /**
     * GET /jeux/prince-de-yop/progression
     */
    public function show(Request $request)
    {
        $product = $this->resolveProduct();
        if (!$product) {
            return response()->json(['error' => 'Jeu non disponible'], 404);
        }

        if (!$this->hasAccess($request, $product)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $progress = GameProgress::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        $data = array_merge(self::DEFAULTS, $progress->data ?? []);

        return response()->json($this->payload($data, false));
    }

    /**
     * POST /jeux/prince-de-yop/progression
     * Corps : {type: progress|level_complete|game_complete, ...}
     */
    public function store(Request $request)
    {
        $product = $this->resolveProduct();
        if (!$product) {
            return response()->json(['error' => 'Jeu non disponible'], 404);
        }

        if (!$this->hasAccess($request, $product)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $type = $request->input('type');

        $rules = match ($type) {
            'progress' => [
                'level' => ['required', 'integer', 'min:0', 'max:' . self::LAST_LEVEL],
                'bonus' => ['required', 'integer', 'min:0', 'max:' . self::MAX_BONUS, 'lte:level'],
            ],
            'level_complete' => [
                'level'  => ['required', 'integer', 'min:0', 'max:' . self::LAST_LEVEL],
                'time'   => ['required', 'integer', 'min:0', 'max:' . self::MAX_LEVEL_TIME],
                'deaths' => ['required', 'integer', 'min:0', 'max:' . self::MAX_DEATHS],
            ],
            'game_complete' => [
                'deaths' => ['required', 'integer', 'min:0', 'max:' . self::MAX_DEATHS],
            ],
            default => null,
        };

        if ($rules === null) {
            return response()->json(['error' => 'Type de message inconnu'], 422);
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['error' => 'Données invalides', 'details' => $validator->errors()], 422);
        }
        $input = $validator->validated();

        $userId = $request->user()->id;

        GameProgress::firstOrCreate(
            ['user_id' => $userId, 'product_id' => $product->id],
            ['data' => self::DEFAULTS]
        );

        return DB::transaction(function () use ($userId, $product, $type, $input) {
            $progress = GameProgress::where('user_id', $userId)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $data = array_merge(self::DEFAULTS, $progress->data ?? []);

            if ($type === 'progress') {
                $level = (int) $input['level'];

                // Partie rejouée depuis le début : on garde la meilleure progression.
                if ($level <= $data['level']) {
                    return response()->json($this->payload($data, false));
                }

                if ($level !== $data['level'] + 1) {
                    return response()->json(['error' => 'Progression incohérente'] + $this->payload($data, false), 422);
                }

                $data['level'] = $level;
                $data['bonus'] = (int) $input['bonus'];
                $data['best_level'] = max((int) $data['best_level'], $level);
            } elseif ($type === 'level_complete') {
                $level = (int) $input['level'];

                if ($level >= $data['best_level']) {
                    return response()->json(['error' => 'Niveau non franchi'] + $this->payload($data, false), 422);
                }

                $key = (string) $level;
                $best = $data['best_times'][$key] ?? null;
                if ($best === null || $input['time'] < $best) {
                    $data['best_times'][$key] = (int) $input['time'];
                }
                $data['total_deaths'] += (int) $input['deaths'];
            } else { // game_complete
                if ($data['level'] !== self::LAST_LEVEL) {
                    return response()->json(['error' => 'Dernier niveau non atteint'] + $this->payload($data, false), 422);
                }

                $data['completed'] = true;
                $data['completions']++;
                $data['first_completed_at'] ??= now()->toIso8601String();
                $data['best_level'] = self::LAST_LEVEL + 1;
                $data['total_deaths'] += (int) $input['deaths'];
                $data['level'] = 0;
                $data['bonus'] = 0;
            }

            $progress->data = $data;
            $progress->save();

            return response()->json($this->payload($data, true));
        });
    }
}
