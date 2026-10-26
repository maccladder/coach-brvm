<?php

namespace App\Checkout\Providers;

use App\Checkout\CheckoutRedirect;
use App\Checkout\Payable;
use App\Checkout\StartsClassicFlow;
use App\Http\Controllers\CaurisController;
use App\Models\CaurisAchat;
use App\Models\GameSave;
use App\Models\User;
use App\Services\CaurisService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaurisPayable extends BasePayable implements StartsClassicFlow
{
    public function __construct(private CaurisService $caurisService) {}

    public function resolve(Request $request, ?User $user): Payable
    {
        $pack = (string) $request->input('pack');
        $conf = config('cauris.packs')[$pack] ?? null;

        if (!$conf) {
            throw CheckoutRedirect::to(route('jeu'), 'error', 'Pack de cauris inconnu.');
        }

        $solde = (int) (GameSave::where('user_id', $user->id)->value('solde') ?? 0);
        $fmt   = fn (int $n) => number_format($n, 0, ',', ' ');

        return new Payable(
            type: 'cauris',
            itemKey: 'pack:' . $pack,
            title: $fmt((int) $conf['cauris']) . ' cauris — Garba Master',
            amount: (int) $conf['prix'],
            email: $user->email,
            successUrl: route('jeu'),
            successLabel: 'Voir mon solde',
            badges: ['Crédit immédiat', 'Nouveau solde : ' . $fmt($solde + (int) $conf['cauris']) . ' cauris'],
            params: ['pack' => $pack],
            offerActivation: false,
            icon: '🐚',
            context: ['pack' => $pack, 'metadata' => ['pack' => $pack, 'cauris' => (int) $conf['cauris']]],
        );
    }

    public function createPending(Payable $payable, ?User $user): string
    {
        // Identique à CaurisController::acheter
        return $this->caurisService->creerAchatEnAttente($user->id, $payable->context['pack'])->reference;
    }

    public function validate(array $data): void
    {
        $this->caurisService->validerPaiementPaystack($data);
    }

    public function isPaid(string $reference): bool
    {
        return CaurisAchat::where('reference', $reference)->whereNotNull('credited_at')->exists();
    }

    public function classicForm(Payable $payable): array
    {
        return ['action' => route('checkout.mm.classic', 'cauris'), 'fields' => $payable->params];
    }

    public function startClassic(Request $request, ?User $user): Response
    {
        return $this->redirectFromLegacyJson(
            $request,
            fn () => app()->call([app(CaurisController::class), 'acheter']),
            route('jeu')
        );
    }
}
