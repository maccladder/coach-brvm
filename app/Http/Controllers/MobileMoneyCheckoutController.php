<?php

namespace App\Http\Controllers;

use App\Checkout\CheckoutRedirect;
use App\Checkout\Payable;
use App\Checkout\PayableProvider;
use App\Checkout\StartsClassicFlow;
use App\Models\MobileMoneyCheckout;
use App\Services\Payments\PaystackMobileMoneyCheckout;
use Illuminate\Http\Request;

/**
 * Checkout Mobile Money personnalisé, commun à tous les types de paiement
 * (activation par type dans config/checkout.php).
 */
class MobileMoneyCheckoutController extends Controller
{
    private const SESSION_ATTEMPTS = 'mm_checkout_refs';

    public function __construct(private PaystackMobileMoneyCheckout $checkout) {}

    /** Écran 1 : ce que tu achètes + opérateur + numéro */
    public function show(Request $request, string $type)
    {
        [$provider, $guard] = $this->provider($request, $type);
        if ($guard) return $guard;

        try {
            $payable = $provider->resolve($request, $request->user());
        } catch (CheckoutRedirect $r) {
            return $this->follow($r);
        }

        return view('checkout.mobile-money', [
            'type'        => $type,
            'payable'     => $payable,
            'operators'   => $this->checkout->operators(),
            'classic'     => $provider->classicForm($payable),
            'whatsapp'    => $this->whatsappUrl($payable),
            'phonePrefix' => config('checkout.phone_prefix', '+225'),
            'window'      => $this->checkout->windowSeconds(),
            'simulating'  => $this->checkout->simulating(),
            'backUrl'     => url()->previous() !== url()->current() ? url()->previous() : url('/'),
        ]);
    }

    /** Lance le charge Mobile Money (JSON) */
    public function pay(Request $request, string $type)
    {
        [$provider, $guard] = $this->provider($request, $type);
        if ($guard) return response()->json(['redirect' => $guard->getTargetUrl()], 409);

        $request->merge(['phone' => preg_replace('/\D+/', '', (string) $request->input('phone'))]);

        $validated = $request->validate([
            'operator' => ['required', 'string', 'in:' . implode(',', array_keys($this->checkout->chargeOperators()))],
            'phone'    => ['required', 'digits:10'],
        ], [
            'operator.required' => 'Choisis ton opérateur.',
            'operator.in'       => 'Choisis ton opérateur.',
            'phone.required'    => 'Saisis ton numéro.',
            'phone.digits'      => 'Le numéro doit faire 10 chiffres (ex. 07 07 12 34 56).',
        ]);

        try {
            $payable = $provider->resolve($request, $request->user());
        } catch (CheckoutRedirect $r) {
            if ($r->flashMessage) session()->flash($r->flashType ?? 'info', $r->flashMessage);
            return response()->json(['redirect' => $r->url], 409);
        }

        $attempt = $this->checkout->start($provider, $payable, $request->user(), $validated['operator'], $validated['phone']);

        $refs = (array) $request->session()->get(self::SESSION_ATTEMPTS, []);
        $request->session()->put(self::SESSION_ATTEMPTS, array_slice(array_unique([...$refs, $attempt->reference]), -20));

        return response()->json($this->checkout->present($attempt));
    }

    /** Polling (toutes les 4 s) : lit la base, Paystack en secours */
    public function status(Request $request, string $reference)
    {
        $attempt = $this->attempt($request, $reference);

        return response()->json($this->checkout->present($this->checkout->refresh($attempt)));
    }

    public function otp(Request $request, string $reference)
    {
        $attempt = $this->attempt($request, $reference);

        $request->validate(['otp' => ['required', 'string', 'regex:/^\d{3,8}$/']], [
            'otp.required' => 'Saisis le code reçu.',
            'otp.regex'    => 'Le code ne contient que des chiffres.',
        ]);

        if ($attempt->status === 'send_otp') {
            $attempt = $this->checkout->submitOtp($attempt, (string) $request->input('otp'));
        }

        return response()->json($this->checkout->present($attempt));
    }

    /**
     * Flux Paystack classique lancé depuis le checkout : "Autre moyen de paiement" (types dont
     * l'ancien flux n'est pas un simple formulaire) et Wave tant que son Charge API n'est pas
     * activé (operator=wave → page Paystack limitée au Mobile Money). Le retour Paystack passe
     * par le callback habituel du type (produit débloqué, mes cours…).
     */
    public function classic(Request $request, string $type)
    {
        abort_unless($this->checkout->exists($type), 404);

        if ($request->input('operator') === 'wave') {
            config(['services.paystack.channels' => ['mobile_money']]);
        }

        $provider = $this->checkout->provider($type);
        abort_unless($provider instanceof StartsClassicFlow, 404);

        if ($provider->requiresAuth() && !$request->user()) {
            return redirect()->guest(route('login'));
        }

        try {
            return $provider->startClassic($request, $request->user());
        } catch (CheckoutRedirect $r) {
            return $this->follow($r);
        }
    }

    // ─── Interne ───────────────────────────────────────────────────────────

    /** @return array{0: ?PayableProvider, 1: ?\Illuminate\Http\RedirectResponse} */
    private function provider(Request $request, string $type): array
    {
        // Drapeau à false : le type n'utilise pas ce checkout
        abort_unless($this->checkout->isEnabled($type), 404);

        $provider = $this->checkout->provider($type);

        if ($provider->requiresAuth() && !$request->user()) {
            return [$provider, redirect()->guest(route('login'))];
        }

        // CHECKOUT_MM_ALLOWED_EMAILS rempli : réservé à ces comptes
        abort_unless($this->checkout->isAllowed($request->user()), 404);

        return [$provider, null];
    }

    private function attempt(Request $request, string $reference): MobileMoneyCheckout
    {
        $attempt = MobileMoneyCheckout::where('reference', $reference)->firstOrFail();

        $owned = $attempt->user_id
            ? (int) $attempt->user_id === (int) $request->user()?->id
            : in_array($reference, (array) $request->session()->get(self::SESSION_ATTEMPTS, []), true);

        abort_unless($owned, 404);

        return $attempt;
    }

    private function follow(CheckoutRedirect $r)
    {
        $redirect = redirect($r->url);

        return $r->flashMessage ? $redirect->with($r->flashType ?? 'info', $r->flashMessage) : $redirect;
    }

    private function whatsappUrl(Payable $payable): string
    {
        $message = "Bonjour, j'ai besoin d'aide pour payer « {$payable->title} » sur Boursiv (compte : {$payable->email}).";

        return 'https://wa.me/' . config('checkout.whatsapp') . '?text=' . rawurlencode($message);
    }
}
