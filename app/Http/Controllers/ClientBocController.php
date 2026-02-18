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
use Barryvdh\DomPDF\Facade\Pdf;

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
    $clientBoc->amount            = (int) env('CINETPAY_PRICE_BOC', 500);
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

public function latestPublic()
{
    // ✅ La dernière BOC "disponible" est toujours J-1 (pas de BOC du jour J)
    $boc = \App\Models\ClientBoc::where('is_public', true)
        ->whereDate('boc_date', '<=', Carbon::yesterday()->toDateString())
        ->orderByDesc('boc_date')
        ->first();

    if (!$boc) {
        return redirect()->route('landing')->with(
            'error',
            "La dernière BOC disponible (J-1) n'est pas encore publiée."
        );
    }

    return redirect()->route('client-bocs.show', $boc);
}


public function paymentReturn(
    ClientBoc $clientBoc,
    CinetpayService $cinetpay
) {
    // 1) Vérifier le statut chez CinetPay
    $status = $cinetpay->checkPayment($clientBoc->transaction_id);

    if ($status === 'ACCEPTED') {

        // 2) Marquer le BOC comme "paid" si ce n'est pas déjà fait
        if ($clientBoc->status !== 'paid') {
            $clientBoc->status = 'paid';
            $clientBoc->save();
        }

        // 3) 👉 Rediriger vers la page de transition /processing
        return redirect()
            ->route('client-bocs.processing', $clientBoc)
            ->with('success', 'Paiement réussi, ton analyse est en cours.');
    }

    // Cas refusé / annulé
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
    // ✅ PDF prioritaire = DailyBoc
    $storagePath = null;

    if (!empty($clientBoc->daily_boc_id)) {
        $daily = \App\Models\DailyBoc::find($clientBoc->daily_boc_id);
        $storagePath = $daily?->file_path; // ex: "bocs/xxxx.pdf"
    }

    // fallback si jamais (upload client)
    if (!$storagePath) {
        $storagePath = $clientBoc->stored_path;
    }

    if (!$storagePath) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Aucun fichier PDF associé à ce BOC.',
            'count'   => 0,
            'data'    => [],
        ], 404);
    }

    $results = $bubbles->extractFromBoc($storagePath);

    return response()->json([
        'status' => 'success',
        'count'  => count($results),
        'data'   => $results,
    ]);
}





    /**
     * Afficher le résultat pour un BOC client.
     */
     public function show(
    ClientBoc $clientBoc,
    AiInterpreter $ai,
    AiVoiceService $voice,
    AvatarService $avatar
) {
    // ✅ On génère si :
    // - c'est un BOC payé (ancienne logique)
    // - OU c'est un BOC public gratuit
    // - OU il est déjà "published" mais pas encore interprété (ex: ton cas)
    $shouldGenerate = (
        empty($clientBoc->interpreted_markdown)
        && (
            $clientBoc->status === 'paid'
            || $clientBoc->status === 'published'
            || (bool) $clientBoc->is_public
        )
    );

    // ✅ Anti double génération (si plusieurs refresh en même temps)
    if ($shouldGenerate && $clientBoc->status !== 'processing') {
        try {
            $clientBoc->status = 'processing';
            $clientBoc->save();

            $this->generateAnalysisForBoc($clientBoc, $ai, $voice, $avatar);

            // Important: regenerateAnalysisForBoc() fait déjà save(),
            // mais on refresh pour renvoyer les valeurs à la vue
            $clientBoc->refresh();

            // Une fois OK on remet publié
            if ($clientBoc->status === 'processing') {
                $clientBoc->status = 'published';
                $clientBoc->save();
                $clientBoc->refresh();
            }
        } catch (\Throwable $e) {
            \Log::error(
                'Erreur génération analyse BOC '.$clientBoc->id.' : '.$e->getMessage(),
                ['exception' => $e]
            );

            // on évite de rester bloqué sur processing si ça a crash
            if ($clientBoc->status === 'processing') {
                $clientBoc->status = 'published';
                $clientBoc->save();
            }
        }
    }

    $audioPath = $clientBoc->audio_path;

    return view('client_bocs.show', [
        'boc'       => $clientBoc,
        'audioPath' => $audioPath,
    ]);
}

// pdf
public function downloadPdf(Request $request, ClientBoc $clientBoc)
{
    // 1) Récupérer l’image base64 envoyée par le front
    $chartBase64 = $request->input('chart_image');

    $chartPath = null;

    if ($chartBase64) {
        // Nettoyer le prefix data:
        $chartBase64 = preg_replace('#^data:image/\w+;base64,#i', '', $chartBase64);
        $binary = base64_decode($chartBase64);

        // 2) Sauver un PNG dans storage/app/public/boc_charts
        $fileName = 'boc-bubbles-'.$clientBoc->id.'.png';
        $relativePath = 'boc_charts/'.$fileName;

        \Storage::disk('public')->put($relativePath, $binary);

        $chartPath = storage_path('app/public/'.$relativePath);
    }

    // 3) Préparer le contenu texte
    $markdown = $clientBoc->interpreted_markdown ?? 'Analyse non disponible.';

    // 4) Générer le PDF à partir d’une vue Blade
    $pdf = Pdf::loadView('client_bocs.pdf', [
        'boc'       => $clientBoc,
        'markdown'  => $markdown,
        'chartPath' => $chartPath,
    ])->setPaper('a4');

    $filename = 'BOC-'.$clientBoc->id.'-'.$clientBoc->boc_date->format('Y-m-d').'.pdf';

    return $pdf->download($filename);
}

}
