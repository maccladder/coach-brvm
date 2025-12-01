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
        $financials = ClientFinancial::orderByDesc('created_at')->take(20)->get();

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
        $storedPath = $file->store('uploads/client_financials');

        $financial = new ClientFinancial();
        $financial->title             = $request->input('title')
                                        ?: 'Etats financiers '.$request->company.' - '.$request->period;
        $financial->company           = $request->company;
        $financial->period            = $request->period;
        $financial->financial_date    = $request->financial_date
                                        ? Carbon::parse($request->financial_date)->toDateString()
                                        : null;
        $financial->original_filename = $file->getClientOriginalName();
        $financial->stored_path       = $storedPath;
        $financial->amount            = (int) env('CINETPAY_TEST_AMOUNT', 100);
        $financial->status            = 'pending';
        $financial->transaction_id    = Str::uuid()->toString();

        // dd($financial->transaction_id);

        $financial->save();

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
    private function generateAnalysisForFinancial(
        ClientFinancial $financial,
        AiInterpreter $ai,
        AiVoiceService $voice,
        AvatarService $avatar
    ): void {
        // 1) Construire la demande pour l’IA
        $meta = [
            'company' => $financial->company,
            'period'  => $financial->period,
            'file'    => $financial->stored_path,
        ];

        // 👉 tu adapteras l’implémentation dans AiInterpreter
        // pour faire un prompt spécial "états financiers"
        $markdown = $ai->interpretFinancial($meta);

        $financial->interpreted_markdown = $markdown;

        // 2) Nettoyer pour faire un texte court pour l’avatar
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

        $textForAvatar = mb_substr($textForAvatar, 0, 900);

        // 3) Avatar vidéo
        $avatarUrl = $avatar->generateTalkingHead($textForAvatar);
        if ($avatarUrl) {
            $financial->avatar_video_url = $avatarUrl;
        }

        // 4) Audio
        $audioPath = $voice->makeAudioFromMarkdown(
            $financial->interpreted_markdown ?? '',
            'clientfinancial-' . $financial->id
        );
        $financial->audio_path = $audioPath;

        $financial->save();
    }

    public function paymentReturn(
    ClientFinancial $financial,
    CinetpayService $cinetpay,
    AiInterpreter $ai,
    AiVoiceService $voice,
    AvatarService $avatar
) {
    // On recharge depuis la base pour être sûr d'avoir un vrai enregistrement
    $financial = ClientFinancial::findOrFail($financial->id);

    // Vérifier le statut chez CinetPay en utilisant le transaction_id existant
    $status = $cinetpay->checkPayment($financial->transaction_id);

    if ($status === 'ACCEPTED') {

        // 1) Marquer comme payé avec un UPDATE explicite
        if ($financial->status !== 'paid') {
            ClientFinancial::where('id', $financial->id)->update([
                'status' => 'paid',
            ]);
            $financial->status = 'paid'; // garder l'instance en phase
        }

        // 2) (Re)générer l'analyse si quelque chose manque
        if (
            empty($financial->interpreted_markdown) ||
            empty($financial->avatar_video_url) ||
            empty($financial->audio_path)
        ) {
            $this->generateAnalysisForFinancial($financial, $ai, $voice, $avatar);
        }

        // 3) Redirection vers la page résultat (comme pour les BOC)
        return redirect()
            ->route('client-financials.show', $financial)
            ->with('success', 'Paiement réussi, ton analyse est prête !');
    }

    // Paiement refusé / annulé
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
                $this->generateAnalysisForFinancial($financial, $ai, $voice, $avatar);
            }
        }

        return response()->json(['message' => 'ok']);
    }

    // On branchera ici la même page "processing" que pour les BOC
    public function processing(ClientFinancial $financial)
    {
        return view('client_financials.processing', [
            'financial'  => $financial,
            'showUrl'    => route('client-financials.show', $financial),
            'statusUrl'  => route('client-financials.status', $financial),
        ]);
    }

    public function status(ClientFinancial $financial)
    {
        return response()->json([
            'ready' => !empty($financial->interpreted_markdown),
        ]);
    }

    public function show(ClientFinancial $financial)
    {
        return view('client_financials.show', [
            'financial' => $financial,
            'audioPath' => $financial->audio_path,
        ]);
    }
}
