<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Retransmet à ADOM CI (autre application sur le même compte Paystack) un webhook
 * reçu ici : corps brut inchangé + x-paystack-signature, pour qu'ADOM CI puisse
 * vérifier la signature avec la même clé secrète Paystack.
 * En queue pour ne jamais retarder la réponse 200 envoyée à Paystack.
 */
class ForwardAdomCiWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    public function __construct(
        public string $url,
        public string $rawPayload,
        public string $signature,
        public string $reference,
    ) {}

    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(): void
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'Content-Type'         => 'application/json',
                'x-paystack-signature' => $this->signature,
            ])
            ->withBody($this->rawPayload, 'application/json')
            ->post($this->url);

        if (!$response->successful()) {
            Log::warning('ADOM CI webhook forward: réponse non 2xx', [
                'reference' => $this->reference,
                'status'    => $response->status(),
                'attempt'   => $this->attempts(),
            ]);
            // Relance avec backoff ; après $tries échecs le job part dans failed_jobs
            throw new \RuntimeException('ADOM CI a répondu HTTP ' . $response->status());
        }

        Log::info('ADOM CI webhook forward: transmis', [
            'reference' => $this->reference,
            'status'    => $response->status(),
        ]);
    }
}
