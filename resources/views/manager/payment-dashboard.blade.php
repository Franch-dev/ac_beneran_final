@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran - AC Beneran')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-money-check-alt"></i> Verifikasi Pembayaran</h1>
            <p class="page-subtitle">Konfirmasi pembayaran dari pelanggan</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Filters -->
    <div class="glass-card" style="margin-bottom: 24px;">
        <form action="{{ route('manager.payments') }}" method="GET" class="search-form">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="search-input" placeholder="Cari order number, masjid...">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Masjid</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr id="order-{{ $order->id }}">
                        <td>
                            <div class="order-num">#{{ $order->order_number }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $order->service_date->format('d M Y') }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">{{ $order->masjid->name }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600;">Rp {{ number_format($order->invoice->total_price ?? 0, 0, ',', '.') }}</div>
                        </td>
                        <td>
                            @if($order->status === 'payment_verified')
                                <span class="status-badge status-pending">Pembayaran Terverifikasi</span>
                            @elseif($order->status === 'waiting_payment')
                                <span class="status-badge" style="background: var(--info-soft); color: var(--info);">Menunggu Pembayaran</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div class="action-btns" style="justify-content: flex-end;">
                                @if($order->status === 'payment_verified' && $order->invoice?->payment_method === 'cash' && !$order->invoice?->cash_confirmed_at)
                                    <button onclick="confirmCash({{ $order->id }})" class="btn btn-warning btn-sm">Konfirmasi Uang Diterima</button>
                                @elseif($order->status === 'payment_verified')
                                    <span style="font-size:0.8rem;color:var(--text-muted);">Selesaikan order dari monitoring</span>
                                @else
                                    <button onclick="verifyCash({{ $order->id }}, '{{ $order->invoice->invoice_number ?? '' }}')" class="btn btn-success btn-sm">Tunai</button>
                                    <button onclick="showTransferModal({{ $order->id }}, {{ (float) ($order->invoice->total_price ?? 0) }})" class="btn btn-primary btn-sm">Transfer</button>
                                    <button onclick="verifyQris({{ $order->id }})" class="btn btn-info btn-sm">QRIS</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state" style="padding: 48px 24px;">
                            <div class="empty-icon" style="margin: 0 auto 16px;"><i class="fas fa-check-circle"></i></div>
                            <h3>Tidak ada pembayaran pending</h3>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($orders->hasPages())
            <div style="padding: 16px;">{{ $orders->links() }}</div>
        @endif
    </div>
</div>

<!-- Transfer Modal -->
<div class="overlay" id="transferModal" style="z-index: 2000;">
    <div class="popup active" style="max-width: 480px;">
        <div class="popup-header">
            <h3><i class="fas fa-university"></i> Verifikasi Transfer Bank</h3>
            <button class="popup-close" onclick="closeTransferModal()">&times;</button>
        </div>
        <div class="popup-body">
            <form id="transferForm" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Jumlah Transfer <span class="required">*</span></label>
                    <input type="number" name="transfer_amount" id="transfer_amount" required min="1" step="1000" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Transfer <span class="required">*</span></label>
                    <input type="date" name="transfer_date" id="transfer_date" required class="form-input" value="{{ date('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Bank <span class="required">*</span></label>
                    <select name="bank_name" required class="form-select">
                        <option value="">Pilih bank</option>
                        <option value="BCA">BCA</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="BNI">BNI</option>
                        <option value="BRI">BRI</option>
                        <option value="CIMB">CIMB Niaga</option>
                        <option value="BSI">BSI</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor Referensi <span class="required">*</span></label>
                    <input type="text" name="reference_number" required maxlength="100" class="form-input" placeholder="Nomor referensi bank">
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" rows="2" class="form-textarea" placeholder="Opsional"></textarea>
                </div>
                <div class="popup-actions">
                    <button type="button" onclick="closeTransferModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">Verifikasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentOrderId = null;
    let currentInvoiceTotal = 0;

    function showPaymentMessage(message, type = 'success') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }

        console[type === 'error' ? 'error' : 'info'](message);
    }

    async function confirmPaymentAction(options) {
        if (typeof window.confirmAction !== 'function') {
            return { confirmed: true, values: {} };
        }

        const result = await window.confirmAction(options);
        return result?.confirmed || result === true
            ? { confirmed: true, values: result.values || {} }
            : { confirmed: false, values: {} };
    }

    function paymentHeaders(isJson = true) {
        const headers = {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        };

        if (isJson) {
            headers['Content-Type'] = 'application/json';
        }

        return headers;
    }

    async function postPayment(url, options = {}) {
        const response = await fetch(url, {
            method: 'POST',
            headers: paymentHeaders(options.json !== false),
            body: options.body,
        });

        return response.json();
    }

    async function verifyCash(orderId, invoiceNumber) {
        const confirmation = await confirmPaymentAction({
            type: 'success',
            heading: 'Verifikasi pembayaran tunai?',
            message: 'Pembayaran tunai akan diverifikasi untuk invoice ini.',
            confirmText: 'Ya, Verifikasi',
            details: [{ label: 'Invoice', value: invoiceNumber }],
        });

        if (!confirmation.confirmed) return;

        try {
            const data = await postPayment(`/payments/${orderId}/verify-cash`);
            if (!data.success) throw new Error(data.message || 'Gagal verifikasi');
            showPaymentMessage(data.message, 'success');
            location.reload();
        } catch (error) {
            showPaymentMessage(error.message || 'Terjadi kesalahan.', 'error');
        }
    }

    function showTransferModal(orderId, invoiceTotal) {
        currentOrderId = orderId;
        currentInvoiceTotal = Number(invoiceTotal) || 0;
        const amountInput = document.getElementById('transfer_amount');
        amountInput.min = String(Math.max(currentInvoiceTotal, 1));
        amountInput.value = currentInvoiceTotal > 0 ? String(currentInvoiceTotal) : '';
        const modal = document.getElementById('transferModal');
        modal.classList.add('active');
        modal.style.display = 'block';
    }

    function closeTransferModal() {
        currentOrderId = null;
        const modal = document.getElementById('transferModal');
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.getElementById('transferForm').reset();
    }

    document.getElementById('transferForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!currentOrderId) return;

        const formData = new FormData(this);
        const amount = Number(formData.get('transfer_amount'));
        if (amount < currentInvoiceTotal) {
            showPaymentMessage('Jumlah transfer kurang dari total invoice.', 'warning');
            return;
        }

        const confirmation = await confirmPaymentAction({
            type: 'success',
            heading: 'Verifikasi transfer?',
            message: 'Transfer akan diverifikasi dengan nominal dan bukti yang diisi.',
            confirmText: 'Ya, Verifikasi',
            details: [
                { label: 'Nominal', value: `Rp ${amount.toLocaleString('id-ID')}` },
                { label: 'Minimal Invoice', value: `Rp ${currentInvoiceTotal.toLocaleString('id-ID')}` },
            ],
        });

        if (!confirmation.confirmed) return;

        try {
            const data = await postPayment(`/payments/${currentOrderId}/verify-transfer`, {
                json: false,
                body: formData,
            });

            if (!data.success) throw new Error(data.message || 'Gagal verifikasi');
            showPaymentMessage(data.message, 'success');
            closeTransferModal();
            location.reload();
        } catch (error) {
            showPaymentMessage(error.message || 'Terjadi kesalahan.', 'error');
        }
    });

    async function verifyQris(orderId) {
        const confirmation = await confirmPaymentAction({
            type: 'success',
            heading: 'Verifikasi QRIS?',
            message: 'Masukkan referensi pembayaran dari penyedia QRIS.',
            confirmText: 'Ya, Verifikasi',
            fields: [
                {
                    name: 'reference',
                    label: 'Referensi QRIS',
                    placeholder: 'Contoh: QRIS-123456',
                    required: true,
                },
            ],
        });

        if (!confirmation.confirmed) return;

        try {
            const data = await postPayment(`/payments/${orderId}/verify-qris`, {
                body: JSON.stringify({ qris_reference: confirmation.values.reference.trim() }),
            });

            if (!data.success) throw new Error(data.message || 'Gagal verifikasi');
            showPaymentMessage(data.message, 'success');
            location.reload();
        } catch (error) {
            showPaymentMessage(error.message || 'Terjadi kesalahan.', 'error');
        }
    }

    async function confirmCash(orderId) {
        const confirmation = await confirmPaymentAction({
            type: 'success',
            heading: 'Konfirmasi uang tunai diterima?',
            message: 'Pembayaran tunai akan tercatat dan order bisa diselesaikan dari monitoring.',
            confirmText: 'Ya, Konfirmasi',
        });

        if (!confirmation.confirmed) return;

        try {
            const data = await postPayment(`/payments/${orderId}/confirm-cash`);
            if (!data.success) throw new Error(data.message || 'Gagal konfirmasi');
            showPaymentMessage(data.message, 'success');
            location.reload();
        } catch (error) {
            showPaymentMessage(error.message || 'Terjadi kesalahan.', 'error');
        }
    }
</script>
@endsection
