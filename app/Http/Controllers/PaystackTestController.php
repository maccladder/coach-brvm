<?php

namespace App\Http\Controllers;

use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaystackTestController extends Controller
{
    public function form()
    {
        return view('payments.paystack_test');
    }

    public function start(Request $request, PaystackService $paystack)
    {
        $request->validate([
            'email'  => ['required','email'],
            'amount' => ['required','integer','min:100'],
        ]);

        $reference = 'PS_' . Str::uuid()->toString();

        $url = $paystack->initialize([
            'email'        => $request->email,
            'amount'       => (int) $request->amount,
            'currency'     => 'XOF',
            'reference'    => $reference,
            'callback_url' => route('paystack.callback'),
            'metadata'     => [
                'type'   => 'paystack_test',
                'amount' => (int) $request->amount,
            ],
        ]);

        if (!$url) {
            return back()->with('error', "Impossible d'initialiser le paiement Paystack.");
        }

        return redirect()->away($url);
    }

    public function callback(Request $request, PaystackService $paystack)
    {
        $reference = (string) $request->query('reference', $request->query('trxref', ''));

        $result = $reference ? $paystack->verify($reference) : null;

        return view('payments.paystack_result', [
            'reference' => $reference,
            'result'    => $result,
        ]);
    }
}
