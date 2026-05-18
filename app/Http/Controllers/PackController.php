<?php

namespace App\Http\Controllers;

use App\Mail\PackBrvmConfirmationMail;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\PackWhatsappToken;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PackController extends Controller
{
    const COURSE_SLUGS = [
        'brvm-debutant',
        'brvm-intermediaire',
        'brvm-pratique-outils-analyse-portefeuille-virtuel',
    ];

    const PRICE = 30000;

    private function loadCourses()
    {
        return Course::whereIn('slug', self::COURSE_SLUGS)->get();
    }

    // ────────────────────────────────────────────────
    // Page de vente
    // ────────────────────────────────────────────────

    public function show(Request $request)
    {
        $courses = $this->loadCourses();

        $hasAllCourses = false;
        if ($user = $request->user()) {
            $ownedCount = CoursePurchase::where('user_id', $user->id)
                ->whereIn('course_id', $courses->pluck('id'))
                ->whereNotNull('paid_at')
                ->count();
            $hasAllCourses = $ownedCount >= $courses->count();
        }

        return view('pack-brvm.index', compact('courses', 'hasAllCourses'));
    }

    // ────────────────────────────────────────────────
    // Initiation paiement Paystack
    // ────────────────────────────────────────────────

    public function buyPaystack(Request $request, PaystackService $paystack)
    {
        $user    = $request->user();
        $courses = $this->loadCourses();

        $transactionId = 'PACK-BRVM-' . $user->id . '-' . Str::upper(Str::random(10));

        $payment = Payment::create([
            'user_id'        => $user->id,
            'transaction_id' => $transactionId,
            'amount_paid'    => self::PRICE,
            'amount_virtual' => 0,
            'purpose'        => 'pack_brvm',
            'status'         => 'PENDING',
            'credited_at'    => null,
            'meta'           => [
                'type'       => 'pack_brvm',
                'course_ids' => $courses->pluck('id')->all(),
                'provider'   => 'paystack',
            ],
        ]);

        $authUrl = $paystack->initialize([
            'email'        => $user->email,
            'amount'       => self::PRICE,
            'reference'    => $transactionId,
            'callback_url' => route('paystack.pack.callback', [], true),
            'metadata'     => [
                'purpose' => 'pack_brvm',
                'user_id' => $user->id,
            ],
        ]);

        if (!$authUrl) {
            $payment->status = 'FAILED';
            $payment->save();
            return back()->with('error', "❌ Impossible d'initialiser Paystack.");
        }

        return redirect()->away($authUrl);
    }

    // ────────────────────────────────────────────────
    // Callback Paystack (retour utilisateur)
    // ────────────────────────────────────────────────

    public function paystackCallback(Request $request, PaystackService $paystack)
    {
        $reference = (string) ($request->query('reference') ?: $request->query('trxref'));

        Log::info('[PACK][PAYSTACK_CALLBACK] hit', [
            'reference' => $reference,
            'url'       => $request->fullUrl(),
        ]);

        if (!$reference) {
            return redirect()->route('pack.show')->with('error', 'Référence Paystack manquante.');
        }

        $data = $paystack->verify($reference);

        if (!$data || ($data['status'] ?? null) !== 'success') {
            return redirect()->route('pack.show')->with('error', 'Paiement non validé ou annulé.');
        }

        $payment = Payment::where('transaction_id', $reference)->first();

        if (!$payment) {
            Log::error('[PACK][PAYSTACK_CALLBACK] payment not found', ['reference' => $reference]);
            return redirect()->route('pack.show')->with('error', 'Paiement introuvable.');
        }

        $paid = (int) (($data['amount'] ?? 0) / 100);
        if ($paid <= 0 || $paid !== (int) $payment->amount_paid) {
            Log::error('[PACK][PAYSTACK_CALLBACK] amount mismatch', [
                'reference' => $reference,
                'db_amount' => (int) $payment->amount_paid,
                'paid'      => $paid,
            ]);
            return redirect()->route('pack.show')->with('error', 'Montant Paystack invalide.');
        }

        // Idempotence : déjà traité
        if ($payment->status === 'ACCEPTED' && $payment->credited_at) {
            return redirect()->route('pack.merci');
        }

        $user  = User::find($payment->user_id);
        $token = null;

        DB::transaction(function () use ($payment, $reference, $data, $user, &$token) {
            $paymentLocked = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($paymentLocked->status === 'ACCEPTED' && $paymentLocked->credited_at) {
                $token = PackWhatsappToken::where('user_id', $user->id)->latest()->value('token');
                return;
            }

            $courseIds = (array) data_get($paymentLocked->meta, 'course_ids', []);

            foreach ($courseIds as $courseId) {
                $purchase = CoursePurchase::firstOrCreate(
                    ['user_id' => $user->id, 'course_id' => (int) $courseId],
                    ['amount_fcfa' => 0, 'payment_ref' => $reference]
                );
                if (!$purchase->paid_at) {
                    $purchase->paid_at     = now();
                    $purchase->payment_ref = $reference;
                    $purchase->save();
                }
            }

            $token = Str::random(64);
            PackWhatsappToken::create(['user_id' => $user->id, 'token' => $token]);

            $paymentLocked->status      = 'ACCEPTED';
            $paymentLocked->credited_at = now();
            $paymentLocked->meta        = array_merge((array) ($paymentLocked->meta ?? []), [
                'paystack' => [
                    'reference' => $reference,
                    'channel'   => $data['channel'] ?? null,
                    'paid_at'   => $data['paid_at'] ?? null,
                ],
            ]);
            $paymentLocked->save();
        });

        if ($token && $user) {
            $courseIds = (array) data_get($payment->fresh()->meta, 'course_ids', []);
            $courses   = Course::whereIn('id', $courseIds)->get();
            try {
                Mail::to($user->email)->send(new PackBrvmConfirmationMail($user, $token, $courses));
            } catch (\Throwable $e) {
                Log::error('[PACK][PAYSTACK_CALLBACK] email failed', [
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('pack.merci');
    }

    // ────────────────────────────────────────────────
    // Page de succès post-paiement
    // ────────────────────────────────────────────────

    public function merci()
    {
        return view('pack-brvm.merci');
    }

    // ────────────────────────────────────────────────
    // Rejoindre le groupe WhatsApp via token
    // ────────────────────────────────────────────────

    public function joinGroupe(string $token, Request $request)
    {
        $tokenRecord = PackWhatsappToken::where('token', $token)->first();

        if (!$tokenRecord) {
            return view('pack-brvm.groupe-expire', ['reason' => 'invalid']);
        }

        if ($tokenRecord->user_id !== $request->user()->id) {
            return view('pack-brvm.groupe-expire', ['reason' => 'wrong_user']);
        }

        if ($tokenRecord->used) {
            return view('pack-brvm.groupe-expire', ['reason' => 'already_used']);
        }

        $tokenRecord->used    = true;
        $tokenRecord->used_at = now();
        $tokenRecord->save();

        return redirect()->away('https://chat.whatsapp.com/BPnXjc0tNGdEHrvJtaHbvx?mode=gi_t');
    }
}
