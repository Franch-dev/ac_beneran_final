@extends('layouts.app')

@section('title', 'Pembayaran Internal - ' . $order->order_number)

@section('content')
@php
    $invoice = $order->invoice;
    $receipt = $order->receipt;
    $paymentMetadata = $invoice?->payment_metadata ?? [];
    $totalPrice = (float) ($invoice?->total_price ?? 0);
    $statusLabel = \App\Models\ServiceOrder::statusLabel($order->status);
    $paymentMethodLabel = $receipt?->payment_method_label ?? ($invoice?->payment_method ? ucfirst($invoice->payment_method) : 'Belum diverifikasi');
    $qrisPayment = $qrisPayment ?? ['configured' => false, 'image_url' => null, 'reference' => null, 'merchant_name' => null, 'amount' => $totalPrice];
    $transferPayment = $transferPayment ?? ['configured' => false, 'bank_name' => null, 'account_number' => null, 'account_name' => null, 'reference' => null, 'amount' => $totalPrice];
@endphp
<div class="page-container" style="max-width: 960px; margin: 0 auto;">
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="page-title"><i class="fas fa-wallet"></i> Pembayaran Internal</h1>
            <p class="page-subtitle">Akses ini hanya berlaku sementara dari monitoring. Nominal QRIS dan invoice bersifat baca-saja.</p>
        </div>
        <div class="page-actions">
            <a href="{{ $backToMonitoringUrl }}" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Kembali ke Monitoring
            </a>
        </div>
    </div>

    <div class="summary-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 20px;">
        <div class="summary-card summary-card--primary">
            <div class="summary-content">
                <div class="summary-kicker">Order</div>
                <div class="summary-label">{{ $order->order_number }}</div>
                <div class="summary-caption">{{ $order->masjid->name }}</div>
            </div>
        </div>
        <div class="summary-card summary-card--info">
            <div class="summary-content">
                <div class="summary-kicker">Status</div>
                <div class="summary-label">{{ $statusLabel }}</div>
                <div class="summary-caption">Akses sesi aktif {{ $paymentAccessTtlMinutes }} menit</div>
            </div>
        </div>
        <div class="summary-card summary-card--success">
            <div class="summary-content">
                <div class="summary-kicker">Nominal Tepat</div>
                <div class="summary-label">Rp {{ number_format($totalPrice, 0, ',', '.') }}</div>
                <div class="summary-caption">Tidak bisa diubah dari halaman ini</div>
            </div>
        </div>
    </div>

    <div class="glass-card" style="padding: 20px; margin-bottom: 20px;">
        <div style="display:grid; gap:18px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
            <div>
                <h2 style="margin:0 0 12px;">Ringkasan Invoice</h2>
                <div class="detail-chip-stack" style="margin-bottom: 14px;">
                    <span class="detail-chip">Invoice {{ $invoice?->invoice_number ?? '-' }}</span>
                    <span class="detail-chip">Servis {{ $order->service_date->translatedFormat('d F Y') }}</span>
                    @if($order->technicianAssignment)
                    <span class="detail-chip">Teknisi {{ $order->technicianAssignment->technician_name }}</span>
                    @endif
                </div>
                <div style="display:grid; gap:10px;">
                    @foreach($order->serviceDetails as $detail)
                    <div style="display:flex; justify-content:space-between; gap:12px; border-bottom:1px solid rgba(15,23,42,.08); padding-bottom:8px;">
                        <div>
                            <div style="font-weight:600;">{{ $detail->description ?: ($detail->pk_type . ' ' . $detail->brand) }}</div>
                            <div class="table-meta">{{ $detail->quantity }} unit</div>
                        </div>
                        <div style="font-weight:600;">
                            Rp {{ number_format($detail->quantity * (float) ($detail->price_per_unit ?? 0), 0, ',', '.') }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h2 style="margin:0 0 12px;">Blok QRIS Internal</h2>
                <div style="border:1px dashed rgba(15,23,42,.18); border-radius:20px; padding:20px; background:linear-gradient(135deg, rgba(8,145,178,.08), rgba(14,165,233,.03));">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                        <strong>QRIS Pembayaran</strong>
                        <span class="status-badge {{ $order->status === 'payment_verified' ? 'status-payment_verified' : '' }}" style="{{ $order->status === 'waiting_payment' ? 'background: var(--info-soft); color: var(--info);' : '' }}">
                            {{ $order->status === 'payment_verified' ? 'Terverifikasi' : 'Menunggu Scan' }}
                        </span>
                    </div>
                    @if($qrisPayment['configured'] && $qrisPayment['image_url'])
                        <div style="display:flex; justify-content:center; margin-bottom:16px;">
                            <img
                                src="{{ $qrisPayment['image_url'] }}"
                                alt="QRIS {{ $qrisPayment['merchant_name'] ?? 'Pembayaran' }} {{ $invoice?->invoice_number }}"
                                style="width:100%; max-width:320px; aspect-ratio:1/1; object-fit:contain; border-radius:16px; background:#fff; padding:12px; border:1px solid rgba(15,23,42,.08);"
                            >
                        </div>
                    @else
                        <div class="alert alert-warning" style="margin-bottom:16px;">
                            QRIS dinamis belum dikonfigurasi di server. Isi <code>PAYMENT_QRIS_PAYLOAD</code> di <code>.env</code>.
                        </div>
                    @endif
                    <div style="font-size:1.1rem; font-weight:700; margin-bottom:6px;">Bayar tepat Rp {{ number_format($totalPrice, 0, ',', '.') }}</div>
                    <div class="table-meta" style="margin-bottom:10px;">Nominal dan referensi dibuat server-side dari invoice. Tidak ada input nominal manual.</div>
                    <div class="detail-chip-stack">
                        <span class="detail-chip">Merchant: {{ $qrisPayment['merchant_name'] ?? '-' }}</span>
                        <span class="detail-chip">Ref QRIS: {{ $receipt?->qris_reference ?? ($paymentMetadata['qris_reference'] ?? ($qrisPayment['reference'] ?? 'Belum ada')) }}</span>
                        <span class="detail-chip">Metode: {{ $paymentMethodLabel }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card" style="padding: 20px;">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
            <div>
                <h2 style="margin:0;">Status Pembayaran</h2>
                <p class="table-meta" style="margin:4px 0 0;">Frontdesk dan teknisi hanya melihat status. Manager dapat memverifikasi pembayaran.</p>
            </div>
            @if($receipt)
            <a href="{{ route('receipts.print', $receipt) }}" target="_blank" class="btn btn-secondary">
                <i class="fas fa-receipt"></i> Cetak Kuitansi
            </a>
            @endif
        </div>

        <div style="display:grid; gap:14px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom:20px;">
            <div class="detail-chip-stack">
                <span class="detail-chip">Invoice: {{ $invoice?->invoice_number ?? '-' }}</span>
                <span class="detail-chip">Metode: {{ $paymentMethodLabel }}</span>
                <span class="detail-chip">Verifier: {{ $invoice?->payment_verified_by_name ?? '-' }}</span>
            </div>
            <div class="detail-chip-stack">
                <span class="detail-chip">Tanggal Bayar: {{ $receipt?->payment_date?->translatedFormat('d F Y') ?? '-' }}</span>
                <span class="detail-chip">Bank: {{ $receipt?->transfer_bank ?? ($paymentMetadata['bank_name'] ?? ($transferPayment['bank_name'] ?? '-')) }}</span>
                <span class="detail-chip">Ref Transfer: {{ $receipt?->transfer_reference ?? ($paymentMetadata['reference_number'] ?? ($transferPayment['reference'] ?? '-')) }}</span>
            </div>
        </div>

        <div class="glass-card" style="padding:16px; margin-bottom:16px; background:rgba(47,93,80,.05);">
            <div style="display:grid; gap:12px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                <div>
                    <div class="summary-kicker">Transfer Server-side</div>
                    @if($transferPayment['configured'])
                        <div style="font-weight:700;">{{ $transferPayment['bank_name'] }} - {{ $transferPayment['account_number'] }}</div>
                        <div class="table-meta">a.n. {{ $transferPayment['account_name'] ?: '-' }}</div>
                        <div class="table-meta">Ref: {{ $transferPayment['reference'] }}</div>
                    @else
                        <div class="table-meta">Rekening transfer belum dikonfigurasi di <code>.env</code>.</div>
                    @endif
                </div>
                <div>
                    <div class="summary-kicker">QRIS Server-side</div>
                    <div style="font-weight:700;">{{ $qrisPayment['merchant_name'] ?? '-' }}</div>
                    <div class="table-meta">Ref: {{ $qrisPayment['reference'] ?? '-' }}</div>
                    <div class="table-meta">Nominal: Rp {{ number_format((float) ($qrisPayment['amount'] ?? $totalPrice), 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        @if($invoice?->payment_notes)
        <div class="alert alert-info" style="margin-bottom: 16px;">{{ $invoice->payment_notes }}</div>
        @endif

        @if($canManagePayment || $canRecordCashPayment)
            @if($order->status === 'waiting_payment')
            <div class="action-btns">
                @if($canRecordCashPayment)
                <button onclick="verifyCash({{ $order->id }}, @js($invoice?->invoice_number ?? ''))" class="btn btn-success">Tunai</button>
                @endif
                @if($canManagePayment)
                <button onclick="verifyTransfer({{ $order->id }})" class="btn btn-primary" {{ $transferPayment['configured'] ? '' : 'disabled' }}>Transfer</button>
                <button onclick="verifyQris({{ $order->id }})" class="btn btn-info" {{ ($qrisPayment['configured'] && ! empty($qrisPayment['payload']) && ! empty($qrisPayment['image_url'])) ? '' : 'disabled' }}>QRIS</button>
                @endif
            </div>
            @elseif($order->status === 'payment_verified' && $invoice?->payment_method === 'cash' && ! $invoice?->cash_confirmed_at)
            <div class="action-btns">
                <button onclick="confirmCash({{ $order->id }})" class="btn btn-warning">Konfirmasi Uang Tunai Diterima</button>
            </div>
            @else
            <div class="alert alert-success">Pembayaran sudah tercatat. Lanjutkan finalisasi order dari monitoring bila semua administrasi selesai.</div>
            @endif
        @else
        <div class="alert alert-info">Halaman ini bersifat baca-saja untuk role Anda. Jika pembayaran perlu diverifikasi, lanjutkan melalui manager.</div>
        @endif
    </div>
</div>

@if($canManagePayment)
<script>
    const monitoringReturnUrl = @js($backToMonitoringUrl);
    const serverPaymentData = {
        qrisReference: @js($qrisPayment['reference'] ?? null),
        transferReference: @js($transferPayment['reference'] ?? null),
        transferBank: @js($transferPayment['bank_name'] ?? null),
    };

    function showMessage(message, type = 'success') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }

        console[type === 'error' ? 'error' : 'info'](message);
    }

    async function confirmPaymentAction(options) {
        if (typeof window.confirmAction !== 'function') {
            return true;
        }

        const result = await window.confirmAction(options);
        return result?.confirmed || result === true;
    }

    function postJson(url, body = null) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body,
        }).then(response => response.json());
    }

    async function verifyCash(orderId, invoiceNumber) {
        const confirmed = await confirmPaymentAction({
            type: 'success',
            heading: 'Verifikasi pembayaran tunai?',
            message: 'Pembayaran tunai akan diverifikasi untuk invoice ini.',
            confirmText: 'Ya, Verifikasi',
            details: [{ label: 'Invoice', value: invoiceNumber }],
        });

        if (!confirmed) return;

        postJson(`/payments/${orderId}/verify-cash`)
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Gagal verifikasi');
                showMessage(data.message);
                window.location.href = monitoringReturnUrl;
            })
            .catch(error => showMessage(error.message, 'error'));
    }

    async function verifyQris(orderId) {
        const confirmed = await confirmPaymentAction({
            type: 'success',
            heading: 'Verifikasi QRIS?',
            message: 'QRIS akan diverifikasi melalui server dengan referensi tercatat.',
            confirmText: 'Ya, Verifikasi',
            details: [{ label: 'Referensi', value: serverPaymentData.qrisReference || '-' }],
        });

        if (!confirmed) return;

        postJson(`/payments/${orderId}/verify-qris`, JSON.stringify({}))
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Gagal verifikasi');
                showMessage(data.message);
                window.location.href = monitoringReturnUrl;
            })
            .catch(error => showMessage(error.message, 'error'));
    }

    async function verifyTransfer(orderId) {
        const confirmed = await confirmPaymentAction({
            type: 'success',
            heading: 'Verifikasi transfer?',
            message: 'Transfer akan diverifikasi melalui server.',
            confirmText: 'Ya, Verifikasi',
            details: [
                { label: 'Bank', value: serverPaymentData.transferBank || '-' },
                { label: 'Referensi', value: serverPaymentData.transferReference || '-' },
            ],
        });

        if (!confirmed) return;

        postJson(`/payments/${orderId}/verify-transfer`, JSON.stringify({}))
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Gagal verifikasi');
                showMessage(data.message);
                window.location.href = monitoringReturnUrl;
            })
            .catch(error => showMessage(error.message, 'error'));
    }

    async function confirmCash(orderId) {
        const confirmed = await confirmPaymentAction({
            type: 'success',
            heading: 'Konfirmasi uang tunai diterima?',
            message: 'Pembayaran tunai akan tercatat dan order dapat dilanjutkan.',
            confirmText: 'Ya, Konfirmasi',
        });

        if (!confirmed) return;

        postJson(`/payments/${orderId}/confirm-cash`)
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Gagal konfirmasi');
                showMessage(data.message);
                window.location.href = monitoringReturnUrl;
            })
            .catch(error => showMessage(error.message, 'error'));
    }

</script>
@endif
@endsection
