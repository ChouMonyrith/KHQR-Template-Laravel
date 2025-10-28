@extends('layouts.app')

@section('content')
<div class="container text-center py-5">
    <h2 class="mb-3">Pay with KHQR</h2>
    <p>Scan this QR code using your Bakong or compatible banking app.</p>

    <div id="qrcode" class="d-flex justify-content-center my-4"></div>

    <p><strong>Amount:</strong> {{ number_format($order->amount, 2) }} KHR</p>

    <div id="status" class="mt-3 text-muted">Waiting for payment...</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    const payload = @json($order->khqr_payload);
    new QRCode(document.getElementById("qrcode"), {
        text: payload,
        width: 256,
        height: 256,
    });

    // Auto check every 10 seconds
    const checkUrl = "{{ route('khqr.check', $order->id) }}";
    const checkInterval = setInterval(() => {
        fetch(checkUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then(data => {
                if (data.status === 'paid') {
                    document.getElementById('status').textContent = '✅ Payment Successful!';
                    clearInterval(checkInterval);
                } else {
                    document.getElementById('status').textContent = 'Waiting for payment...';
                }
            })
            .catch(err => {
                console.error('KHQR check error:', err);
            });
    }, 10000);
</script>
@endsection
