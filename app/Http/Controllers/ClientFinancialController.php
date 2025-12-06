<?php

namespace App\Http\Controllers;

use App\Models\ClientFinancial;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\AiInterpreter;
use App\Services\AvatarService;
use App\Services\AiVoiceService;
use App\Services\CinetpayService;

class ClientFinancialController extends Controller
{
    public function index()
    {
        $financials = ClientFinancial::orderByDesc('created_at')
            ->take(20)
            ->get();

        return view('client_financials.index', compact('financials'));
    }

    public function create()
    {
        return view('client_financials.create');
    }

    public function store(Request $request, CinetpayService $cinetpay)
    {
        $request->validate([
            'title'          => ['nullable', 'string', 'max:255'],
            'company'        => ['required', 'string', 'max:255'],
            'period'         => ['required', 'string', 'max:255'],  // "Exercice 2024"
            'financial_date' => ['nullable', 'date'],
            'file'           => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('file');
        // disk = "public"
        $storedPath = $file->store('uploads/client_financials', 'public');

        $financial = new ClientFinancial();
        $financial->title             = $request->input('title')
                                        ?: 'Etats financiers ' . $request->company . ' - ' . $request->period;
        $financial->company           = $request->company;
        $financial->period            = $request->period;
        $financial->financial_date    = $request->financial_date
                                        ? Carbon::parse($request->financial_date)->toDateString()
                                        : null;
        $financial->original_filename = $file->getClientOriginalName();
        $financial->stored_path       = $storedPath;
        $financial->amount            = (int) env('CINETPAY_TEST_AMOUNT', 1000);
        $financial->status            = 'pending';
        $financial->transaction_id    = Str::uuid()->toString();
        $financial->save();

        // URLs de retour / notify
        $returnUrl = route('client-financials.payment.return', $financial);
        $notifyUrl = route('client-financials.payment.notify');

        $paymentUrl = $cinetpay->createPayment([
            'transaction_id' => $financial->transaction_id,
            'amount'         => $financial->amount,
            'description'    => $financial->title,
            'notify_url'     => $notifyUrl,
            'return_url'     => $returnUrl,
        ]);

        if (!$paymentUrl) {
            $financial->status = 'failed';
            $financial->save();

            return back()->with('error', 'Impossible d’initier le paiement.');
        }

        return redirect()->away($paymentUrl);
    }

    /**
     * Analyse IA des états financiers + avatar + audio.
     */
   /**
 * Analyse IA des états financiers + avatar + audio.
 */
private function generateAnalysisForFinancial(
    ClientFinancial $financial,
    AiInterpreter $ai,
    AiVoiceService $voice,
    AvatarService $avatar
): void {

    // 🔥 1) Élargir le temps d'exécution PHP pour ce traitement
    if (function_exists('set_time_limit')) {
        @set_time_limit(300); // 300 secondes = 5 minutes
    }
    @ini_set('max_execution_time', '300');

    // 2) Construire la demande pour l’IA (interprétation complète)
    $meta = [
        'company'   => $financial->company,
        'period'    => $financial->period,
        'file_path' => $financial->stored_path,
    ];

    $markdown = $ai->interpretFinancial($meta);
    $financial->interpreted_markdown = $markdown;

    // 3) Préparer un texte court pour l’avatar IA
    $plain = $markdown ?? '';
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

Voici les points essentiels des états financiers de {$financial->company} ({$financial->period}) :

{$mainSummary}

Rappelle-toi : ces informations ne sont pas un conseil d’investissement personnalisé.
Analyse toujours toi-même les entreprises avant d’investir.
TXT;

    // D-ID ne supporte pas trop long → on coupe à 900 chars
    $textForAvatar = mb_substr($textForAvatar, 0, 900);

    // 4) Avatar IA (D-ID)
    $avatarUrl = $avatar->generateTalkingHead($textForAvatar);
    if ($avatarUrl) {
        $financial->avatar_video_url = $avatarUrl;
    }

    // 5) Audio IA (OpenAI TTS)
    $audioPath = $voice->makeAudioFromMarkdown(
        $financial->interpreted_markdown ?? '',
        'clientfinancial-' . $financial->id
    );
    $financial->audio_path = $audioPath;

    // 6) Save final
    $financial->save();
}



   public function paymentReturn(
    ClientFinancial $clientFinancial,
    CinetpayService $cinetpay,
    AiInterpreter $ai,
    AiVoiceService $voice,
    AvatarService $avatar
) {
    $financial = $clientFinancial;

    // Vérifier le paiement
    $status = $cinetpay->checkPayment($financial->transaction_id);

    if ($status === 'ACCEPTED') {

        // On marque "paid" si ce n'est pas déjà fait
        if ($financial->status !== 'paid') {
            $financial->status = 'paid';
            $financial->save();
        }

        // 🟢 En LOCAL : CinetPay ne peut pas appeler /payment/notify,
        // donc on fait le gros boulot ici pour que ça marche chez toi.
        if (app()->environment('local')) {

            if (
                empty($financial->interpreted_markdown) ||
                empty($financial->avatar_video_url) ||
                empty($financial->audio_path)
            ) {
                $this->generateAnalysisForFinancial($financial, $ai, $voice, $avatar);
            }

            // On envoie directement sur la page finale
            return redirect()
                ->route('client-financials.show', $financial)
                ->with('success', 'Paiement réussi, ton analyse a été générée.');
        }

        // 🟣 En PROD (domaine public) : on laisse paymentNotify() faire le travail
        return redirect()
            ->route('client-financials.processing', $financial)
            ->with('success', 'Paiement réussi, ton analyse est en cours de préparation.');
    }

    return redirect()
        ->route('client-financials.index')
        ->with('error', 'Paiement non validé ou annulé.');
}



   public function paymentNotify(
    Request $request,
    CinetpayService $cinetpay,
    AiInterpreter $ai,
    AiVoiceService $voice,
    AvatarService $avatar
) {
    \Log::debug('PAYMENT NOTIFY payload', $request->all());
    $transactionId = $request->input('transaction_id');
    \Log::debug('PAYMENT NOTIFY transaction_id = ' . var_export($transactionId, true));

    if (!$transactionId) {
        return response()->json(['message' => 'no transaction_id'], 400);
    }

    $financial = ClientFinancial::where('transaction_id', $transactionId)->first();
    if (!$financial) {
        return response()->json(['message' => 'financial not found'], 404);
    }

    $status = $cinetpay->checkPayment($transactionId);

    if ($status === 'ACCEPTED') {
        if ($financial->status !== 'paid') {
            $financial->status = 'paid';
            $financial->save();
        }

        if (
            empty($financial->interpreted_markdown) ||
            empty($financial->avatar_video_url) ||
            empty($financial->audio_path)
        ) {
            // Gros boulot côté serveur (en prod quand le site sera public)
            $this->generateAnalysisForFinancial($financial, $ai, $voice, $avatar);
        }
    }

    return response()->json(['message' => 'ok']);
}


    public function processing(ClientFinancial $clientFinancial)
    {
        return view('client_financials.processing', [
            'financial' => $clientFinancial,
            'showUrl'   => route('client-financials.show', $clientFinancial),
            'statusUrl' => route('client-financials.status', $clientFinancial),
        ]);
    }

    public function status(ClientFinancial $clientFinancial)
    {
        $clientFinancial->refresh();

        return response()->json([
            'ready' =>
                !empty($clientFinancial->interpreted_markdown) &&
                !empty($clientFinancial->avatar_video_url) &&
                !empty($clientFinancial->audio_path),
        ]);
    }

    public function show(ClientFinancial $clientFinancial)
    {
        return view('client_financials.show', [
            'financial' => $clientFinancial,
            'audioPath' => $clientFinancial->audio_path,
        ]);
    }
}
