<?php

namespace App\Services;

class BrvmMarketAiService extends BrvmActionsAiService
{
    /**
     * Compatibilité: certaines parties du code appellent encore ce nom.
     * On renvoie une liste "stocks" utilisable par le wallet/market.
     */
    public function fetchCloseAndChangeFromSite(): array
    {
        $rows = $this->fetchMarketTableFromSite();

        return array_map(function ($s) {
            return [
                'ticker'    => $s['ticker'] ?? null,
                'name'      => $s['name'] ?? null,
                'close'     => $s['close'] ?? null,
                'change'    => $s['change'] ?? null,
                'buy_price' => $s['buy_price'] ?? ($s['open'] ?? $s['close'] ?? $s['prev'] ?? null),
            ];
        }, $rows);
    }
}
