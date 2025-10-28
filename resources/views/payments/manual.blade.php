@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            
            <!-- Header -->
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900">Bakong KHQR Payment</h2>
                <p class="mt-2 text-gray-600">Enter amount and generate QR to pay</p>
            </div>

            <!-- Amount Input Form -->
            <form method="GET" action="{{ route('khqr.pay') }}" class="flex justify-center mb-6 space-x-4">
                <div class="relative">
                    <input 
                        type="number" 
                        name="amount" 
                        value="{{ $amount ?? 1000 }}" 
                        min="100"
                        placeholder="Enter amount"
                        class="pl-12 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-48"
                    >
                    <span class="absolute left-3 top-2.5 text-gray-500">KHR</span>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition duration-150 ease-in-out">
                    Generate QR
                </button>
            </form>

            <!-- QR Code Section -->
            <div class="text-center space-y-6">
                <h4 class="text-xl font-semibold text-gray-900">
                    Amount: {{ number_format($amount ?? 0, 2) }} KHR
                </h4>

                <div id="status" class="mt-3 py-2 px-4 inline-flex rounded-full bg-yellow-100 text-yellow-800">
                    Waiting for payment...
                </div>

                <div id="qrcode" class="flex justify-center p-4 bg-white rounded-lg shadow-sm"></div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for polling -->
<form id="checkPaymentForm" action="{{ route('khqr.check') }}" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="md5" value="{{ $md5 }}">
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const payload = @json($payload);
const statusElement = document.getElementById('status');

if (payload) {
    new QRCode(document.getElementById("qrcode"), {
        text: payload,
        width: 256,
        height: 256,
        correctLevel: QRCode.CorrectLevel.H
    });

    pollPaymentStatus();
} else {
    statusElement.textContent = "No QR payload available.";
}

function updateStatus(message, type='pending') {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        success: 'bg-green-100 text-green-800',
        error: 'bg-red-100 text-red-800'
    };
    statusElement.className = `mt-3 py-2 px-4 inline-flex rounded-full ${colors[type]}`;
    statusElement.textContent = message;
}

// Polling
let pollCount = 0;
const maxPolls = 60; // 5 min max
function pollPaymentStatus() {
    const form = document.getElementById('checkPaymentForm');
    const formData = new FormData(form);

    fetch(form.action, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {

        console.log(data)

        if (data.status === 'paid') {
            updateStatus('Payment Successful!', 'success');
        } else if (data.status === 'not_found') {
            updateStatus('Payment record not found', 'error');
        } else if (data.status === 'pending') {
            updateStatus('Waiting for payment...', 'pending');
            pollCount++;
            if (pollCount < maxPolls) setTimeout(pollPaymentStatus, 5000);
        } else if (data.status === 'bakong_error' || data.status === 'server_error') {
            updateStatus('Server error, try again later', 'error');
        } else {
            updateStatus('Unexpected response, retrying...', 'pending');
            pollCount++;
            if (pollCount < maxPolls) setTimeout(pollPaymentStatus, 5000);
        }
    })
    .catch(err => {
        console.error('Error checking payment:', err);
        updateStatus('Error checking payment, retrying...', 'pending');
        pollCount++;
        if (pollCount < maxPolls) setTimeout(pollPaymentStatus, 5000);
    });
}
</script>
@endsection
