<?php

namespace App\Http\Controllers;

use App\Models\AdminGrant;
use App\Models\MarketplaceProduct;
use App\Services\TwilioVerifyService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class PhoneVerificationController extends Controller
{
    // ──────────────────────────────────────────────
    // Afficher le formulaire
    // ──────────────────────────────────────────────

    public function showForm(Request $request)
    {
        $user = $request->user();

        return view('phone-verification', [
            'countries'      => config('otp.countries'),
            'deadline'       => config('otp.reward_deadline'),
            'alreadyClaimed' => (bool) $user->phone_reward_claimed,
            'isVerified'     => ! is_null($user->phone_verified_at),
            'currentPhone'   => $user->phone,
        ]);
    }

    // ──────────────────────────────────────────────
    // Envoyer l'OTP
    // ──────────────────────────────────────────────

    public function sendOtp(Request $request, TwilioVerifyService $twilio): RedirectResponse
    {
        $user    = $request->user();
        $rlKey   = 'otp-send:' . $user->id;
        $max     = (int) config('otp.throttle_send_max', 3);
        $decay   = (int) config('otp.throttle_send_decay', 60); // minutes

        if (RateLimiter::tooManyAttempts($rlKey, $max)) {
            $wait = (int) ceil(RateLimiter::availableIn($rlKey) / 60);
            return back()->withErrors(['phone' =>
                "Trop de tentatives. Réessaie dans {$wait} minute(s)."
            ])->withInput();
        }

        $request->validate([
            'country_code' => ['required', 'string', 'in:' . implode(',', array_keys(config('otp.countries')))],
            'phone_local'  => ['required', 'string', 'regex:/^[0-9]+$/'],
        ]);

        $country   = config('otp.countries.' . $request->country_code);
        $local     = $request->phone_local;
        $expected  = (int) $country['digits'];

        if (strlen($local) !== $expected) {
            return back()->withInput()->withErrors(['phone_local' =>
                "Le numéro doit comporter {$expected} chiffres pour {$country['name']}."
            ]);
        }

        $phoneE164 = $country['prefix'] . $local;

        // Incrémenter le compteur AVANT l'appel API (une tentative = un envoi)
        RateLimiter::hit($rlKey, $decay * 60);

        $sent = $twilio->sendOtp($phoneE164);

        if (! $sent) {
            return back()->withInput()->withErrors(['phone' =>
                "Envoi impossible. Vérifie le numéro et réessaie dans quelques instants."
            ]);
        }

        // Stocker le numéro E.164 en session pour l'étape de vérification
        $request->session()->put('otp_phone', $phoneE164);

        return back()->with('otp_sent', true)->withInput();
    }

    // ──────────────────────────────────────────────
    // Vérifier le code OTP
    // ──────────────────────────────────────────────

    public function verifyOtp(Request $request, TwilioVerifyService $twilio): RedirectResponse
    {
        $user  = $request->user();
        $rlKey = 'otp-verify:' . $user->id;
        $max   = (int) config('otp.throttle_verify_max', 3);
        $decay = (int) config('otp.throttle_verify_decay', 10); // minutes

        if (RateLimiter::tooManyAttempts($rlKey, $max)) {
            $wait = (int) ceil(RateLimiter::availableIn($rlKey) / 60);
            return back()->withErrors(['otp_code' =>
                "Trop de tentatives. Réessaie dans {$wait} minute(s)."
            ]);
        }

        $request->validate([
            'otp_code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ]);

        $phoneE164 = $request->session()->get('otp_phone');

        if (! $phoneE164) {
            return back()->withErrors(['otp_code' =>
                "Session expirée. Recommence en saisissant ton numéro."
            ]);
        }

        // Incrémenter avant l'appel API
        RateLimiter::hit($rlKey, $decay * 60);

        $approved = $twilio->checkOtp($phoneE164, $request->otp_code);

        if (! $approved) {
            return back()->withErrors(['otp_code' =>
                "Code incorrect ou expiré. Vérifie le SMS et réessaie."
            ]);
        }

        // ✅ Code approuvé
        RateLimiter::clear($rlKey);
        $request->session()->forget('otp_phone');

        // Idempotency : si déjà revendiqué, on met juste à jour le téléphone
        if ($user->phone_reward_claimed) {
            $user->update([
                'phone'             => $phoneE164,
                'phone_verified_at' => now(),
            ]);
            return redirect()->route('dashboard')
                ->with('success', "✅ Numéro vérifié avec succès !");
        }

        // Marquer le téléphone comme vérifié + récompense revendiquée
        $user->update([
            'phone'                => $phoneE164,
            'phone_verified_at'    => now(),
            'phone_reward_claimed' => true,
        ]);

        // ── Logique de récompense ──────────────────
        $deadline      = Carbon::parse(config('otp.reward_deadline'))->endOfDay();
        $rewardGranted = false;
        $alreadyOwned  = false;

        if (now()->lte($deadline)) {
            $product = MarketplaceProduct::where('slug', config('otp.reward_product_slug'))->first();

            if ($product) {
                $alreadyOwned = $user->purchasedProducts()
                        ->where('marketplace_products.id', $product->id)
                        ->wherePivot('status', 'paid')
                        ->exists()
                    || $product->isGrantedTo($user);

                if (! $alreadyOwned) {
                    AdminGrant::create([
                        'user_id'        => $user->id,
                        'grantable_type' => MarketplaceProduct::class,
                        'grantable_id'   => $product->id,
                        'reason'         => 'Récompense vérification téléphone OTP',
                        'granted_by'     => null,
                        'granted_at'     => now(),
                        'expires_at'     => null,
                    ]);

                    $rewardGranted = true;

                    Log::info('OTP reward granted', [
                        'user_id'    => $user->id,
                        'product_id' => $product->id,
                        'phone'      => $this->maskPhone($phoneE164),
                    ]);
                }
            }
        }

        if ($rewardGranted) {
            return redirect()->route('phone.verify.success');
        }

        if ($alreadyOwned) {
            return redirect()->route('dashboard')
                ->with('success', "✅ Numéro vérifié ! Tu possèdes déjà Abidjan Run 🎮");
        }

        // Deadline passée ou produit introuvable — vérification OK, pas de récompense
        return redirect()->route('dashboard')
            ->with('success', "✅ Numéro vérifié avec succès !");
    }

    // ──────────────────────────────────────────────
    // Page de succès post-récompense
    // ──────────────────────────────────────────────

    public function success(Request $request)
    {
        $user = $request->user();

        // Protection accès direct à l'URL sans avoir vérifié
        if (! $user->phone_reward_claimed) {
            return redirect()->route('dashboard');
        }

        $product = MarketplaceProduct::where('slug', config('otp.reward_product_slug'))->first();

        return view('phone-verification-success', compact('product'));
    }

    // ──────────────────────────────────────────────
    // Masquage téléphone pour logs
    // ──────────────────────────────────────────────

    private function maskPhone(string $phone): string
    {
        $len = strlen($phone);
        if ($len <= 8) {
            return str_repeat('*', $len);
        }
        return substr($phone, 0, 4) . str_repeat('*', $len - 8) . substr($phone, -4);
    }
}
