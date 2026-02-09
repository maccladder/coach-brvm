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

        $reference = 'PS_' . Str::uuid()->toString(); // référence unique

        $url = $paystack->createPayment([
            'transaction_id' => $reference,
            'amount'         => (int) $request->amount,
            'currency'       => 'XOF',
            'customer_email' => $request->email,
            'return_url'     => route('paystack.callback'), // callback
            'metadata'       => [
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
        $reference = (string) $request->query('reference', '');

        $result = $paystack->checkPayment($reference);

        return view('payments.paystack_result', [
            'reference' => $reference,
            'result'    => $result,
        ]);
    }
}
