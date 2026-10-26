<?php

namespace App\Checkout\Providers;

use App\Checkout\CheckoutRedirect;
use App\Checkout\Payable;
use App\Checkout\StartsClassicFlow;
use App\Http\Controllers\VirtualWalletController;
use App\Models\Payment;
use App\Models\User;
use App\Models\VirtualWallet;
use App\Services\Payments\PaystackWalletTopupService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class WalletPayable extends BasePayable implements StartsClassicFlow
{
    // Mêmes bornes que VirtualWalletController::topupConfirm
    private const MIN = 1000;
    private const MAX = 1000000;

    public function __construct(private PaystackWalletTopupService $walletService) {}

    public function resolve(Request $request, ?User $user): Payable
    {
        $amount = (int) $request->input('amount');

        if ($amount < self::MIN || $amount > self::MAX) {
            throw CheckoutRedirect::to(route('wallet.index'), 'error', 'Montant de recharge invalide.');
        }

        $virtual    = $amount * 100;
        $balance    = (float) (VirtualWallet::where('user_id', $user->id)->value('balance') ?? 0);
        $newBalance = number_format($balance + $virtual, 0, ',', ' ');

        return new Payable(
            type: 'wallet',
            itemKey: 'amount:' . $amount,
            title: 'Recharge du portefeuille virtuel',
            amount: $amount,
            email: $user->email,
            successUrl: route('wallet.index'),
            successLabel: 'Voir mon solde',
            badges: ['Crédit immédiat', 'Nouveau solde estimé : ' . $newBalance],
            params: ['amount' => $amount],
            offerActivation: false,
            icon: '💼',
            context: ['amount_virtual' => $virtual],
        );
    }

    public function createPending(Payable $payable, ?User $user): string
    {
        $reference = 'WALLET_' . now()->format('YmdHis') . '_' . Str::upper(Str::random(6));

        // Identique à VirtualWalletController::topupConfirm + topupPayPaystack
        Payment::create([
            'user_id'        => $user->id,
            'transaction_id' => $reference,
            'amount_paid'    => $payable->amount,
            'amount_virtual' => $payable->context['amount_virtual'],
            'purpose'        => 'wallet_topup',
            'status'         => 'PENDING',
            'meta'           => [
                'source'  => 'mobile_money_checkout',
                'channel' => 'mobile_money_checkout',
            ],
        ]);

        return $reference;
    }

    public function metadata(Payable $payable, ?User $user): array
    {
        return array_merge(parent::metadata($payable, $user), ['purpose' => 'wallet_topup']);
    }

    public function validate(array $data): void
    {
        $this->walletService->handleChargeSuccess($data);
    }

    public function isPaid(string $reference): bool
    {
        return $this->paymentIsPaid($reference);
    }

    public function classicForm(Payable $payable): array
    {
        return ['action' => route('checkout.mm.classic', 'wallet'), 'fields' => $payable->params];
    }

    /** Ancien flux : paiement en attente (topupConfirm) puis page Paystack (topupPayPaystack). */
    public function startClassic(Request $request, ?User $user): Response
    {
        $payable = $this->resolve($request, $user);

        $payment = Payment::create([
            'user_id'        => $user->id,
            'transaction_id' => 'WALLET_' . now()->format('YmdHis') . '_' . Str::upper(Str::random(6)),
            'amount_paid'    => $payable->amount,
            'amount_virtual' => $payable->context['amount_virtual'],
            'purpose'        => 'wallet_topup',
            'status'         => 'PENDING',
            'meta'           => ['source' => 'wallet_topup_confirm'],
        ]);

        $request->merge(['payment_id' => $payment->id, 'classic' => 1]);

        return app()->call([app(VirtualWalletController::class), 'topupPayPaystack']);
    }
}
