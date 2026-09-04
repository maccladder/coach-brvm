<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ValidHcaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.hcaptcha.secret_key');

        if (empty($secret)) {
            Log::warning('hCaptcha: HCAPTCHA_SECRET_KEY manquante, inscription refusée par sécurité.');
            $fail('La vérification anti-robot est indisponible pour le moment.');

            return;
        }

        if (empty($value)) {
            $fail('Merci de valider le contrôle anti-robot.');

            return;
        }

        $response = Http::asForm()->post('https://hcaptcha.com/siteverify', [
            'secret' => $secret,
            'response' => $value,
        ]);

        if (! $response->successful() || ! ($response->json('success') ?? false)) {
            $fail('La vérification anti-robot a échoué. Merci de réessayer.');
        }
    }
}
