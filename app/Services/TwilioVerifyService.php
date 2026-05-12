<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioVerifyService
{
    private Client $client;
    private string $serviceSid;

    public function __construct()
    {
        $sid   = config('services.twilio.account_sid');
        $token = config('services.twilio.auth_token');
        $vsid  = config('services.twilio.verify_service_sid');

        if (!$sid || !$token || !$vsid) {
            throw new \RuntimeException(
                'Twilio credentials manquants. Vérifie TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN et TWILIO_VERIFY_SERVICE_SID dans .env'
            );
        }

        $this->client     = new Client($sid, $token);
        $this->serviceSid = $vsid;
    }

    /**
     * Envoie un OTP SMS au numéro E.164 fourni.
     * Retourne true si Twilio accepte l'envoi, false sinon.
     */
    public function sendOtp(string $phoneE164): bool
    {
        try {
            $verification = $this->client
                ->verify->v2
                ->services($this->serviceSid)
                ->verifications
                ->create($phoneE164, 'sms');

            return $verification->status === 'pending';

        } catch (TwilioException $e) {
            Log::error('TwilioVerify::sendOtp failed', [
                'phone'   => $this->maskPhone($phoneE164),
                'tw_code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Vérifie le code OTP pour le numéro E.164 fourni.
     * Retourne true si approuvé, false sinon (code erroné, expiré, ou erreur API).
     */
    public function checkOtp(string $phoneE164, string $code): bool
    {
        try {
            $check = $this->client
                ->verify->v2
                ->services($this->serviceSid)
                ->verificationChecks
                ->create(['to' => $phoneE164, 'code' => $code]);

            return $check->status === 'approved';

        } catch (TwilioException $e) {
            Log::error('TwilioVerify::checkOtp failed', [
                'phone'   => $this->maskPhone($phoneE164),
                'tw_code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Masque un numéro E.164 pour les logs.
     * Exemple : +2250102345678 → +225******5678
     */
    private function maskPhone(string $phone): string
    {
        $len = strlen($phone);
        if ($len <= 8) {
            return str_repeat('*', $len);
        }

        $prefix = substr($phone, 0, 4);  // +225
        $suffix = substr($phone, -4);     // 4 derniers chiffres
        $middle = str_repeat('*', $len - 8);

        return $prefix . $middle . $suffix;
    }
}
