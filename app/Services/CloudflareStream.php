<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CloudflareStream
{
    private string $accountId;
    private string $token;

    public function __construct()
    {
        $this->accountId = config('services.cloudflare_stream.account_id');
        $this->token = config('services.cloudflare_stream.token');
    }

    private function baseUrl(): string
    {
        return "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/stream";
    }

    private function client()
    {
        return Http::withToken($this->token)->acceptJson();
    }

    public function getVideo(string $uid): array
    {
        $res = $this->client()->get($this->baseUrl() . "/{$uid}")->json();

        if (!($res['success'] ?? false)) {
            throw new \RuntimeException('Cloudflare getVideo failed: ' . json_encode($res));
        }

        return $res['result'];
    }

    public function createPlaybackToken(string $uid, int $expSeconds): string
    {
        $res = $this->client()->post($this->baseUrl() . "/{$uid}/token", [
            "exp" => time() + $expSeconds,
        ])->json();

        if (!($res['success'] ?? false)) {
            throw new \RuntimeException('Cloudflare token failed: ' . json_encode($res));
        }

        return $res['result']['token'];
    }
}
