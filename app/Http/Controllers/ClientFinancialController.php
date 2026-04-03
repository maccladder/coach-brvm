<?php

namespace App\Http\Controllers;

use App\Mail\AdminFinancialPaymentMail;
use App\Models\ClientFinancial;
use App\Services\AiInterpreter;
use App\Services\AvatarService;
use App\Services\AiVoiceService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ClientFinancialController extends Controller
{
    public function index()
    {
        $financials = ClientFinancial::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return view('client_financials.index', compact('financials'));
    }

    public function create()
    {
        return view('client_financials.create');
    }

    public function store(Request $request, PaystackService $paystack)
    {
        $request->validate([
            'title'          => ['nullable', 'string', 'max:255'],
            'company'        => ['required', 'string', 'max:255'],
            'period'         => ['required', 'string', 'max:255'],
            'financial_date' => ['nullable', 'date'],
            'client_email'   => [auth()->check() ? 'nullable' : 'required', 'nullable', 'email', 'max:255'],
            'file'           => ['required', 'file', 'mimes:pdf,xlsx,xls,csv', 'max:10240'],
        ]);

        $file       = $request->file('file');
        $storedPath = $file->store('uploads/client_financials', 'public');

        // Email : priorité auth user, sinon champ formulaire
        $email = auth()->user()?->email ?? $request->input('client_email');

        $financial = new ClientFinancial();
        $financial->user_id           = auth()->id();
        $financial->client_email      = $email;
        $financial->title             = $request->input('title')
                                        ?: 'Etats financiers ' . $request->company . ' - ' . $request->period;
        $financial->company           = $request->company;
        $financial->period            = $request->period;
        $financial->financial_date    = $request->financial_date
                                        ? Carbon::parse($request->financial_date)->toDateString()
                                        : null;
        $financial->original_filename = $file->getClientOriginalName();
        $financial->stored_path       = $storedPath;
        $financial->amount            = (int) env('PAYSTACK_PRICE_FINANCIAL', 1000);
        $financial->status            = 'pending';
        $financial->transaction_id    = Str::uuid()->toString();
        $financial->save();

        $callbackUrl = route('client-financials.payment.callback');

        $paymentUrl = $paystack->initialize([
            'email'        => $email,
            'amount'       => $financial->amount,
            'currency'     => 'XOF',
            'reference'    => $financial->transaction_id,
            'callback_url' => $callbackUrl,
            'metadata'     => [
                'financial_id' => $financial->id,
                'company'      => $financial->company,
                'period'       => $financial->period,
            ],
        ]);

        if (!$paymentUrl) {
            $financial->status = 'failed';
            $financial->save();
            return back()->with('error', 'Impossible d\'initier le paiement. Veuillez réessayer.');
        }

        return redirect()->away($paymentUrl);
    }

    /**
     * Callback Paystack — GET /client-financials/payment/callback?reference=xxx
     */
    public function paystackCallback(
        Request $request,
        PaystackService $paystack,
        AiInterpreter $ai,
        AiVoiceService $voice,
        AvatarService $avatar
    ) {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (!$reference) {
            return redirect()->route('client-financials.index')
                ->with('error', 'Référence de paiement manquante.');
        }

        $financial = ClientFinancial::where('transaction_id', $reference)->first();

        if (!$financial) {
            return redirect()->route('client-financials.index')
                ->with('error', 'Transaction introuvable.');
        }

        // Vérifier avec Paystack
        $data = $paystack->verify($reference);

        if (!$data || ($data['status'] ?? '') !== 'success') {
            return redirect()->route('client-financials.index')
                ->with('error', 'Paiement non validé ou annulé.');
        }

        // Marquer payé (idempotent)
        if ($financial->status !== 'paid') {
            $financial->status = 'paid';
            $financial->save();
        }

        // Mail admin (anti-doublon via notified_at)
        $this->notifyAdmins($financial);

        // En local : génération synchrone
        if (app()->environment('local')) {
            if (empty($financial->interpreted_markdown)) {
                $this->generateAnalysisForFinancial($financial, $ai, $voice, $avatar);
            }
            return redirect()->route('client-financials.show', $financial)
                ->with('success', 'Paiement réussi, ton analyse a été générée.');
        }

        // En prod : page d'attente + génération en arrière-plan si pas encore faite
        if (empty($financial->interpreted_markdown)) {
            $this->generateAnalysisForFinancial($financial, $ai, $voice, $avatar);
        }

        return redirect()->route('client-financials.processing', $financial)
            ->with('success', 'Paiement réussi, ton analyse est en cours de préparation.');
    }

    /**
     * Analyse IA + avatar + audio
     */
    private function generateAnalysisForFinancial(
        ClientFinancial $financial,
        AiInterpreter $ai,
        AiVoiceService $voice,
        AvatarService $avatar
    ): void {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        @ini_set('max_execution_time', '300');

        $meta = [
            'company'   => $financial->company,
            'period'    => $financial->period,
            'file_path' => $financial->stored_path,
        ];

        $markdown = $ai->interpretFinancial($meta);
        $financial->interpreted_markdown = $markdown;

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

Rappelle-toi : ces informations ne sont pas un conseil d'investissement personnalisé.
Analyse toujours toi-même les entreprises avant d'investir.
TXT;

        $textForAvatar = mb_substr($textForAvatar, 0, 900);

        $avatarUrl = $avatar->generateTalkingHead($textForAvatar);
        if ($avatarUrl) {
            $financial->avatar_video_url = $avatarUrl;
        }

        $audioPath = $voice->makeAudioFromMarkdown(
            $financial->interpreted_markdown ?? '',
            'clientfinancial-' . $financial->id
        );
        $financial->audio_path = $audioPath;

        $financial->save();
    }

    /**
     * Envoie un mail aux admins (une seule fois)
     */
    private function notifyAdmins(ClientFinancial $financial): void
    {
        if ($financial->notified_at) {
            return;
        }

        try {
            Mail::to([
                'maccladder@gmail.com',
                'ghislainkouadiodjaha@gmail.com',
            ])->send(new AdminFinancialPaymentMail($financial));

            $financial->notified_at = now();
            $financial->save();
        } catch (\Throwable $e) {
            Log::error('AdminFinancialPaymentMail failed: ' . $e->getMessage(), [
                'financial_id' => $financial->id,
            ]);
        }
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
