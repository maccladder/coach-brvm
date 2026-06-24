<?php

namespace App\Http\Controllers;

use App\Models\CaurisAchat;
use App\Services\CaurisService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CaurisController extends Controller
{
    /**
     * GET /api/jeu/packs
     * Retourne la liste des packs de cauris depuis la config.
     */
    public function packs(Request $request)
    {
        $packs = collect(config('cauris.packs'))->map(fn ($p, $key) => [
            'key'    => $key,
            'prix'   => $p['prix'],
            'cauris' => $p['cauris'],
        ])->values();

        return response()->json(['packs' => $packs]);
    }

    /**
     * POST /api/jeu/acheter
     * Crée l'achat en attente et initialise la transaction Paystack.
     */
    public function acheter(Request $request, CaurisService $caurisService, PaystackService $paystack)
    {
        $validPacks = array_keys(config('cauris.packs', []));

        $request->validate([
            'pack' => ['required', 'string', 'in:' . implode(',', $validPacks)],
        ]);

        $user = $request->user();
        $pack = $request->input('pack');

        $achat = $caurisService->creerAchatEnAttente($user->id, $pack);

        $authUrl = $paystack->initialize([
            'email'        => $user->email,
            'amount'       => $achat->prix,          // FCFA — PaystackService multiplie par 100
            'currency'     => 'XOF',
            'reference'    => $achat->reference,
            'callback_url' => route('jeu.paiement.retour'),
            'metadata'     => [
                'user_id'  => $user->id,
                'pack'     => $pack,
                'cauris'   => $achat->cauris,
            ],
        ]);

        if (!$authUrl) {
            Log::error('CaurisController::acheter: Paystack init failed', [
                'user_id'   => $user->id,
                'reference' => $achat->reference,
            ]);
            return response()->json(['error' => "Impossible d'initialiser le paiement."], 502);
        }

        Log::info('CaurisController::acheter: paiement initialisé', [
            'user_id'   => $user->id,
            'reference' => $achat->reference,
            'pack'      => $pack,
        ]);

        return response()->json(['authorization_url' => $authUrl]);
    }

    /**
     * GET /jeu/paiement/retour
     * Page de retour après paiement Paystack (filet secondaire — le webhook est la source de vérité).
     * Idempotent grâce à credited_at dans crediterCauris().
     */
    public function callback(Request $request, PaystackService $paystack, CaurisService $caurisService)
    {
        $reference = (string) ($request->query('reference') ?: $request->query('trxref', ''));

        if (!$reference) {
            Log::warning('CaurisController::callback: référence manquante', $request->all());
            return redirect()->route('jeu')->with('error', 'Référence de paiement manquante.');
        }

        Log::info('CaurisController::callback: retour Paystack', [
            'reference' => $reference,
            'query'     => $request->all(),
        ]);

        // Retrouve l'achat en base (sans verrouiller — lecture seule ici)
        $achat = CaurisAchat::where('reference', $reference)->first();

        if (!$achat) {
            Log::error('CaurisController::callback: achat non trouvé', ['reference' => $reference]);
            return redirect()->route('jeu')->with('error', 'Achat introuvable.');
        }

        // Si déjà crédité via webhook : déjà traité, redirige en succès
        if ($achat->credited_at !== null) {
            return redirect()->route('jeu')->with('success', 'Paiement déjà enregistré. Tes cauris sont disponibles.');
        }

        // Vérification Paystack
        $data = $paystack->verify($reference);

        if (!$data) {
            return redirect()->route('jeu')->with('error', 'Impossible de vérifier le paiement auprès de Paystack.');
        }

        if (($data['status'] ?? null) !== 'success') {
            Log::warning('CaurisController::callback: paiement non success', [
                'reference' => $reference,
                'status'    => $data['status'] ?? null,
            ]);
            return redirect()->route('jeu')->with('error', 'Paiement non validé : ' . ($data['status'] ?? 'unknown'));
        }

        // Vérification du montant (anti-fraude)
        $paidFcfa = (int) round(((int) ($data['amount'] ?? 0)) / 100);
        if ($paidFcfa <= 0 || $paidFcfa !== (int) $achat->prix) {
            Log::error('CaurisController::callback: montant incorrect', [
                'reference' => $reference,
                'attendu'   => $achat->prix,
                'recu'      => $paidFcfa,
            ]);
            return redirect()->route('jeu')->with('error', 'Montant du paiement invalide.');
        }

        // Marque ACCEPTED si pas déjà fait (le webhook peut l'avoir fait avant)
        if ($achat->status !== 'ACCEPTED') {
            $achat->update(['status' => 'ACCEPTED']);
        }

        // Crédite les cauris (idempotent — sans effet si déjà fait par le webhook)
        $caurisService->crediterCauris($reference);

        return redirect()->route('jeu')->with('success', number_format($achat->cauris, 0, ',', ' ') . ' cauris crédités !');
    }
}
