<?php

namespace App\Checkout\Providers;

use App\Checkout\Payable;
use App\Http\Controllers\PackController;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\Affiliate\AffiliateCommissionService;
use App\Services\Payments\PaystackPackService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackPayable extends BasePayable
{
    public function __construct(
        private AffiliateCommissionService $affiliateService,
        private PaystackPackService $packService,
    ) {}

    public function resolve(Request $request, ?User $user): Payable
    {
        $courses  = Course::whereIn('slug', PackController::COURSE_SLUGS)->get();
        $checkout = $this->affiliateService->resolveForCheckout($request, 'pack_brvm', null, PackController::PRICE);

        return new Payable(
            type: 'pack',
            itemKey: 'pack_brvm',
            title: 'Pack BRVM Complet',
            amount: (int) $checkout['amount'],
            email: $user->email,
            successUrl: route('pack.merci'),
            successLabel: 'Voir mon pack',
            // Contenu du pack
            badges: $courses->pluck('title')->all(),
            params: array_filter(['affiliate_code' => $request->input('affiliate_code')]),
            icon: '📦',
            context: [
                'course_ids'     => $courses->pluck('id')->all(),
                'affiliate_code' => $checkout['code'] ?? null,
            ],
        );
    }

    public function createPending(Payable $payable, ?User $user): string
    {
        $affiliateCode = $payable->context['affiliate_code'];
        $reference     = 'PACK-BRVM-' . $user->id . '-' . Str::upper(Str::random(10));

        // Identique à PackController::buyPaystack
        Payment::create([
            'user_id'               => $user->id,
            'transaction_id'        => $reference,
            'amount_paid'           => $payable->amount,
            'amount_virtual'        => 0,
            'purpose'               => 'pack_brvm',
            'status'                => 'PENDING',
            'credited_at'           => null,
            'affiliate_code'        => $affiliateCode,
            'affiliate_base_amount' => $affiliateCode ? PackController::PRICE : null,
            'meta'                  => [
                'type'       => 'pack_brvm',
                'course_ids' => $payable->context['course_ids'],
                'provider'   => 'paystack',
                'channel'    => 'mobile_money_checkout',
            ],
        ]);

        return $reference;
    }

    public function metadata(Payable $payable, ?User $user): array
    {
        return array_merge(parent::metadata($payable, $user), ['purpose' => 'pack_brvm']);
    }

    public function validate(array $data): void
    {
        $this->packService->handleChargeSuccess($data);
    }

    public function isPaid(string $reference): bool
    {
        return $this->paymentIsPaid($reference);
    }

    public function classicForm(Payable $payable): array
    {
        return [
            'action' => route('pack.buy.paystack'),
            'fields' => array_filter([
                'classic'        => 1,
                'affiliate_code' => $payable->params['affiliate_code'] ?? null,
            ]),
        ];
    }
}
