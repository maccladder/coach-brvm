<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Societe;

class SocietesWebhookController extends Controller
{
    /**
     * Liste des sociétés cotées (source de vérité : table societes), pour que
     * les workflows n8n n'aient jamais de liste de tickers en dur.
     */
    public function index()
    {
        $societes = Societe::where('is_listed', true)
            ->orderBy('code')
            ->get(['code', 'name', 'sector', 'country', 'listing_date'])
            ->map(fn (Societe $s) => [
                'ticker'       => $s->code,
                'name'         => $s->name,
                'sector'       => $s->sector,
                'country'      => $s->country,
                'listing_date' => $s->listing_date ? substr((string) $s->listing_date, 0, 10) : null,
            ]);

        return response()->json([
            'count'    => $societes->count(),
            'societes' => $societes,
        ]);
    }
}
