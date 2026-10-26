<?php

namespace App\Checkout\Providers;

use App\Checkout\CheckoutRedirect;
use App\Checkout\Payable;
use App\Checkout\StartsClassicFlow;
use App\Http\Controllers\DonationController;
use App\Models\Donation;
use App\Models\User;
use App\Services\Payments\PaystackDonationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dons : ouverts aux visiteurs non connectés. Le nom, l'email et le montant saisis sur
 * /faire-un-don sont gardés en session (pas dans l'URL) par DonationController::initiate.
 */
class DonationPayable extends BasePayable implements StartsClassicFlow
{
    public const SESSION_KEY = 'checkout_mm_don';

    public function __construct(private PaystackDonationService $donationService) {}

    public function requiresAuth(): bool
    {
        return false;
    }

    public function resolve(Request $request, ?User $user): Payable
    {
        $don = (array) $request->session()->get(self::SESSION_KEY, []);

        if (empty($don['donor_email']) || (int) ($don['amount'] ?? 0) < 500) {
            throw CheckoutRedirect::to(route('donation.show'));
        }

        return new Payable(
            type: 'don',
            itemKey: 'don:' . (int) $don['amount'],
            title: 'Don à Boursiv',
            amount: (int) $don['amount'],
            email: (string) $don['donor_email'],
            successUrl: url('/'),
            successLabel: "Retour à l'accueil",
            successTitle: 'Merci !',
            note: 'Merci pour ton soutien 💛',
            offerActivation: false,
            icon: '💛',
            context: [
                'donor_name'  => (string) ($don['donor_name'] ?? ''),
                'donor_email' => (string) $don['donor_email'],
                'metadata'    => ['donor_name' => (string) ($don['donor_name'] ?? '')],
            ],
        );
    }

    public function createPending(Payable $payable, ?User $user): string
    {
        $reference = 'DON-' . strtoupper(Str::random(12));

        // Identique à DonationController::initiate
        Donation::create([
            'reference'   => $reference,
            'donor_name'  => $payable->context['donor_name'],
            'donor_email' => $payable->context['donor_email'],
            'amount'      => $payable->amount,
            'currency'    => 'XOF',
            'status'      => 'PENDING',
            'meta'        => ['provider' => 'paystack', 'channel' => 'mobile_money_checkout'],
        ]);

        return $reference;
    }

    public function metadata(Payable $payable, ?User $user): array
    {
        return array_merge(parent::metadata($payable, $user), ['purpose' => 'donation']);
    }

    public function validate(array $data): void
    {
        $this->donationService->handleChargeSuccess($data);
    }

    public function isPaid(string $reference): bool
    {
        return Donation::where('reference', $reference)->where('status', 'ACCEPTED')->exists();
    }

    public function classicForm(Payable $payable): array
    {
        return [
            'action' => route('donation.initiate'),
            'fields' => [
                'classic'     => 1,
                'donor_name'  => $payable->context['donor_name'],
                'donor_email' => $payable->context['donor_email'],
                'amount'      => $payable->amount,
            ],
        ];
    }

    /** Ancien flux Paystack (page hébergée), ex. Wave tant que son Charge API n'est pas activé */
    public function startClassic(Request $request, ?User $user): Response
    {
        $payable = $this->resolve($request, $user);

        $request->merge([
            'classic'     => 1,
            'donor_name'  => $payable->context['donor_name'],
            'donor_email' => $payable->context['donor_email'],
            'amount'      => $payable->amount,
        ]);

        return app()->call([app(DonationController::class), 'initiate']);
    }
}
