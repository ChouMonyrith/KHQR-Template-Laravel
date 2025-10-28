<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\BakongKHQRService;
use Illuminate\Http\Request;
use KHQR\Helpers\KHQRData;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $bakong;

    public function __construct(BakongKHQRService $bakong)
    {
        $this->bakong = $bakong;
    }

    // Generate QR
    public function manualPay(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:100',
            'currency' => Rule::in([KHQRData::CURRENCY_KHR, KHQRData::CURRENCY_USD])
        ]);

        $amount = $validated['amount'] ?? 1000; // default 1000 KHR
        $currency = $validated['currency'] ?? KHQRData::CURRENCY_KHR;

        $qr = $this->bakong->generateMerchantQR($amount);

        Payment::create([
            'transaction_id' => uniqid('pay_'),
            'amount' => $amount,
            'currency' => $currency,
            'khqr_status' => 'pending',
            'md5' => $qr['md5'],
        ]);

        return view('payments.manual', [
            'payload' => $qr['payload'],
            'amount'  => $amount,
            'md5'     => $qr['md5'],
        ]);
    }

    // Polling check
    public function checkStatus(Request $request)
    {
        $request->validate(['md5' => 'required|string']);
        $md5 = $request->input('md5');

        $payment = Payment::where('md5', $md5)->first();

        if (!$payment) {
            return response()->json(['status' => 'not_found'], 404);
        }

        try {
            Log::info('Checking payment for MD5: ' . $md5);
            // $result = $this->bakong->checkPaymentStatus($md5);

            $result = $this->bakong->checkPaymentStatus($md5);

            if (
            isset($result['responseCode']) &&
            $result['responseCode'] === 0 &&
            isset($result['responseMessage']) &&
            strtolower($result['responseMessage']) === 'success'
        ) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        return response()->json([
            'status' => strtolower($payment->status ?? 'pending'),
            'bakong_response' => $result
        ]);

        } catch (\Exception $e) {
            Log::error('Bakong API error: '.$e->getMessage());
            return response()->json(['status' => 'bakong_error', 'message' => $e->getMessage()], 500);
        }
    }

}
