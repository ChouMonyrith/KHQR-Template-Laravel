<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment; // Assuming you have a Payment model
use App\Services\BakongKHQRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
 

class PaymentWebhookController extends Controller
{

    protected $bakong;

    public function __construct(BakongKHQRService $bakong)
    {
        $this->bakong = $bakong;
    }

    public function handle(Request $request)
    {
        Log::info('Received payment webhook:', $request->all());

        // ✅ 1. Skip signature check in local/dev environment
        if (app()->environment('local')) {
            Log::warning('Skipping webhook signature verification (local mode)');
        } else {
            $secret = config('services.bakong.secret');
            $signature = $request->header('X-Bakong-Signature');
            $expected = hash_hmac('sha256', $request->getContent(), $secret);

            if ($signature !== $expected) {
                Log::warning('Invalid webhook signature');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        // ✅ 2. Extract payment data
        $data = $request->input('data');
        $transactionId = $data['transaction_id'] ?? null;
        $status = $data['status'] ?? null;

        if (!$transactionId || !$status) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // ✅ 3. Update database
        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            Log::warning('Payment not found: ' . $transactionId);
            return response()->json(['error' => 'Payment not found'], 404);
        }

        $payment->status = strtoupper($status);
        $payment->paid_at = now();
        $payment->save();

        Log::info("Payment {$transactionId} marked as {$status}");

        return response()->json(['success' => true]);
    }


    public function recieved(Request $request)
    {
        // Handle the webhook request here
        return response()->json(['message' => 'Webhook received'], 200);
    }

     public function checkStatus(Request $request)
    {
        $request->validate(['md5' => 'required|string']);
        $md5 = $request->md5;
        
        $payment = Payment::where('md5', $md5)->first();

        if (!$payment) {
            return response()->json(['status' => 'not_found'], 404);
        }

        try {
            $result = $this->bakong->checkPaymentStatus($md5);
        } catch (\Exception $e) {
            Log::error('Bakong API error: '.$e->getMessage());
            return response()->json(['status' => 'bakong_error', 'message' => $e->getMessage()], 500);
        }

        // Success response from Bakong
        if (
            isset($result['responseCode'], $result['data']['hash']) &&
            $result['responseCode'] === 0 &&
            $result['data']['hash'] === $md5 && 
            $result['responseMessage'] === 'Success'
        ) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        return response()->json(['status' => strtolower($payment->status ?? 'pending')]);
    }
}
