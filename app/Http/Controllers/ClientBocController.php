<?php

namespace App\Http\Controllers;

use App\Models\ClientBoc;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\AiInterpreter;
use App\Services\AvatarService;
use App\Services\AiVoiceService;
use App\Services\CinetpayService;
use App\Services\BrvmBubbleService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class ClientBocController extends Controller
{
    /**
     * Liste simple des BOC clients (plus tard : pagination + user_id).
     */
    public function index()
    {
        $bocs = ClientBoc::orderByDesc('created_at')->take(20)->get();

        return view('client_bocs.index', compact('bocs'));
    }

    /**
     * Formulaire d’upload d’un BOC client.
     */
    public function create()
    {
        return view('client_bocs.create');
    }

    /**
     * Upload + interprétation IA + avatar + audio.
     */
    public function store(
    Request $request,
    CinetpayService $cinetpay
) {
    $request->validate([
        'title'    => ['nullable', 'string', 'max:255'],
        'boc_date' => ['required', 'date'],
        'file'     => ['required', 'file', 'max:10240'], // 10 Mo
    ]);

    $bocDate = Carbon::parse($request->input('boc_date'))->toDateString();
    $file    = $request->file('file');

    // 1) Stocker le fichier BOC
    $storedPath = $file->store('uploads/client_bocs');

    // 2) Créer l’enregistrement BOC en statut "pending"
    $clientBoc = new ClientBoc();
    $clientBoc->title             = $request->input('title') ?: 'BOC client du '.$bocDate;
    $clientBoc->boc_date          = $bocDate;
    $clientBoc->original_filename = $file->getClientOriginalName();
    $clientBoc->stored_path       = $storedPath;
    $clientBoc->file_path         = $storedPath; // si tu gardes cette colonne
    $clientBoc->amount            = (int) env('CINETPAY_TEST_AMOUNT', 100);
    $clientBoc->status            = 'pending';
    $clientBoc->transaction_id    = Str::uuid()->toString(); // id unique pour CinetPay
    $clientBoc->save();

    // 3) URLs de retour / notification
    $returnUrl = route('client-bocs.payment.return', $clientBoc);
    $notifyUrl = route('client-bocs.payment.notify');

    // 4) Appel CinetPay pour générer le lien de paiement
    $paymentUrl = $cinetpay->createPayment([
        'transaction_id' => $clientBoc->transaction_id,
        'amount'         => $clientBoc->amount,
        'description'    => $clientBoc->title, // ex : "BOC client du 2025-11-30"
        'notify_url'     => $notifyUrl,
        'return_url'     => $returnUrl,
    ]);

    if (!$paymentUrl) {
        // Échec d’init de paiement
        $clientBoc->status = 'failed';
        $clientBoc->save();

        return back()
            ->with('error', 'Impossible d’initier le paiement. Réessaie plus tard.');
    }

    // 5) Rediriger le client vers la page de paiement CinetPay
    return redirect()->away($paymentUrl);
}

private function generateAnalysisForBoc(
    ClientBoc $clientBoc,
    AiInterpreter $ai,
    AiVoiceService $voice,
    AvatarService $avatar
): void {
    $bocDate = $clientBoc->boc_date->toDateString();

    // 1) Construire les données pour l’IA
    $analyses = [[
        'title'     => $clientBoc->title,
        'file_path' => $clientBoc->stored_path,
        'notes'     => null,
    ]];
    $statements = [];

    // 2) Interprétation
    $interpretation = $ai->interpret($analyses, $statements, $bocDate);
    $clientBoc->interpreted_markdown = $interpretation;

    // 3) Texte raccourci pour l’avatar
    $plain = $interpretation ?? '';
    $plain = preg_replace('/^\s*#+\s*/m', '', $plain);
    $plain = preg_replace('/^\s*[-*]\s+/m', '', $plain);
    $plain = str_replace(['**', '*', '_', '`'], '', $plain);
    $plain = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $plain);
    $plain = preg_replace("/\n{2,}/", "\n", $plain);
    $plain = trim($plain);

    $lines       = array_filter(array_map('trim', explode("\n", $plain)));
    $mainLines   = array_slice($lines, 0, 4);
    $mainSummary = implode(' ', $mainLines);

    $textForAvatar = <<<TXT
Bonjour, je suis ton coach BRVM.

Voici les principaux enseignements de ton BOC du {$bocDate} :
{$mainSummary}

N’oublie pas : ceci n’est pas un conseil d’investissement personnalisé.
Analyse toujours toi-même les entreprises et n’investis que l’argent que tu peux te permettre de perdre.
TXT;

    $textForAvatar = mb_substr($textForAvatar, 0, 900);

    // 4) Avatar
    $avatarUrl = $avatar->generateTalkingHead($textForAvatar);

    \Log::info('Avatar URL pour BOC '.$clientBoc->id.' = '.($avatarUrl ?: 'NULL'));
    if ($avatarUrl) {
        $clientBoc->avatar_video_url = $avatarUrl;
    }

    // 5) Audio
    $audioPath = $voice->makeAudioFromMarkdown(
        $clientBoc->interpreted_markdown ?? '',
        'clientboc-' . $clientBoc->id
    );
    $clientBoc->audio_path = $audioPath;

    $clientBoc->save();
}

public function paymentReturn(
    ClientBoc $clientBoc,
    CinetpayService $cinetpay,
    AiInterpreter $ai,
    AiVoiceService $voice,
    AvatarService $avatar
) {
    // Vérifier le statut chez CinetPay
    $status = $cinetpay->checkPayment($clientBoc->transaction_id);

    if ($status === 'ACCEPTED') {

        // 1) S'assurer que le statut est bien "paid"
        if ($clientBoc->status !== 'paid') {
            $clientBoc->status = 'paid';
            $clientBoc->save();
        }

        // 2) (Re)générer l’analyse si quelque chose manque
        if (
            empty($clientBoc->interpreted_markdown) ||
            empty($clientBoc->avatar_video_url) ||
            empty($clientBoc->audio_path)
        ) {
            $this->generateAnalysisForBoc($clientBoc, $ai, $voice, $avatar);
        }

        // 3) Revenir DIRECTEMENT sur la page de résultat, comme avant
        return redirect()
            ->route('client-bocs.show', $clientBoc)
            ->with('success', 'Paiement réussi, ton analyse est prête !');
    }

    // Refusé ou en attente
    return redirect()
        ->route('client-bocs.index')
        ->with('error', 'Paiement non validé ou annulé.');
}






public function paymentNotify(
    Request $request,
    CinetpayService $cinetpay,
    AiInterpreter $ai,
    AiVoiceService $voice,
    AvatarService $avatar
) {
    $transactionId = $request->input('transaction_id');

    if (!$transactionId) {
        return response()->json(['message' => 'no transaction_id'], 400);
    }

    $clientBoc = ClientBoc::where('transaction_id', $transactionId)->first();
    if (!$clientBoc) {
        return response()->json(['message' => 'boc not found'], 404);
    }

    $status = $cinetpay->checkPayment($transactionId);

    if ($status === 'ACCEPTED') {

        if ($clientBoc->status !== 'paid') {
            $clientBoc->status = 'paid';
            $clientBoc->save();
        }

        if (
            empty($clientBoc->interpreted_markdown) ||
            empty($clientBoc->avatar_video_url) ||
            empty($clientBoc->audio_path)
        ) {
            $this->generateAnalysisForBoc($clientBoc, $ai, $voice, $avatar);
        }
    }

    return response()->json(['message' => 'ok']);
}

public function processing(ClientBoc $clientBoc)
{
    return view('client_bocs.processing', [
        'boc'       => $clientBoc,
        'showUrl'   => route('client-bocs.show', $clientBoc),
        'statusUrl' => route('client-bocs.status', $clientBoc),
    ]);
}

public function status(ClientBoc $clientBoc)
{
    // prêt = quand le champ interpreted_markdown est rempli
    return response()->json([
        'ready' => !empty($clientBoc->interpreted_markdown),
    ]);
}

public function bubbles(ClientBoc $clientBoc, BrvmBubbleService $bubbles)
{
    // 1️⃣ Vérifier qu’un PDF est bien stocké
    if (!$clientBoc->stored_path) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Aucun fichier PDF associé à ce BOC.',
            'count'   => 0,
            'data'    => [],
        ], 404);
    }

    // 2️⃣ Appeler le service qui utilise GPT-4.1
    $results = $bubbles->extractFromBoc($clientBoc->stored_path);

    // 3️⃣ Retour propre vers ton front D3.js
    return response()->json([
        'status' => 'success',
        'count'  => count($results),
        'data'   => $results,
    ]);
}




    /**
     * Afficher le résultat pour un BOC client.
     */
    public function show(ClientBoc $clientBoc)
    {
        $audioPath = $clientBoc->audio_path;

        return view('client_bocs.show', [
            'boc'       => $clientBoc,
            'audioPath' => $audioPath,
        ]);
    }
}
