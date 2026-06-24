@extends('layouts.app')

@section('title', 'Edit Invoice - AC Beneran')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <h1 class="page-title">Edit Invoice</h1>
            <p class="page-subtitle">Order #{{ $serviceOrder->order_number }} — {{ $serviceOrder->masjid->name }}</p>
        </div>
    </div>

    <!-- Technician Fee Report (if exists) -->
    @if($serviceOrder->technicianAssignment && $serviceOrder->technicianAssignment->fee_reported)
        <div class="alert alert-warning glass-card" style="padding: 20px; margin-bottom: 24px;">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--warning); margin-bottom: 12px;">
                <i class="fas fa-exclamation-triangle"></i> Laporan Biaya Tambahan dari Teknisi
            </h3>
            <div class="summary-grid" style="grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 0.875rem;">
                <div>
                    <span style="color: var(--text-muted);">Teknisi:</span>
                    <span style="margin-left: 8px; font-weight: 500;">{{ $serviceOrder->technicianAssignment->technician_name }}</span>
                </div>
                <div>
                    <span style="color: var(--text-muted);">Jumlah:</span>
                    <span style="margin-left: 8px; font-weight: 600; color: var(--danger);">Rp {{ number_format($serviceOrder->technicianAssignment->fee_amount, 0, ',', '.') }}</span>
                </div>
                <div style="grid-column: span 2;">
                    <span style="color: var(--text-muted);">Deskripsi:</span>
                    <span style="margin-left: 8px;">{{ $serviceOrder->technicianAssignment->fee_description }}</span>
                </div>
                @if($serviceOrder->technicianAssignment->fee_tools_materials)
                    <div style="grid-column: span 2;">
                        <span style="color: var(--text-muted);">Alat/Bahan:</span>
                        <span style="margin-left: 8px;">{{ $serviceOrder->technicianAssignment->fee_tools_materials }}</span>
                    </div>
                @endif
            </div>
            <button onclick="addTechFeeToInvoice()" class="btn btn-warning btn-sm" style="margin-top: 16px;">
                <i class="fas fa-plus"></i> Tambahkan ke Invoice
            </button>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Invoice Editor (Left 2 cols) -->
        <div>
            <div class="card glass-card">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; font-weight: 600;">Invoice #{{ $invoice->invoice_number }}</h2>
                    <span class="status-badge status-info">
                        {{ ucfirst(str_replace('_', ' ', $serviceOrder->status)) }}
                    </span>
                </div>
                <div class="card-body">
                    <!-- Line Items -->
                    <div class="table-container">
                        <div class="overflow-x-auto">
                            <table class="data-table" id="invoiceTable">
                                <thead>
                                    <tr>
                                        <th style="text-align: left;">Deskripsi</th>
                                        <th style="text-align: center; width: 80px;">Qty</th>
                                        <th style="text-align: right; width: 130px;">Harga</th>
                                        <th style="text-align: right; width: 130px;">Subtotal</th>
                                        <th style="width: 40px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="lineItems">
                                    @foreach($invoice->serviceDetails ?? [] as $detail)
                                        <tr class="line-item" data-id="{{ $detail->id }}">
                                            <td>
                                                <input type="text" value="{{ $detail->description }}" class="form-input item-desc" style="width: 100%;">
                                            </td>
                                            <td style="text-align: center;">
                                                <input type="number" value="{{ $detail->quantity }}" min="1" class="form-input item-qty" style="width: 70px; text-align: center;">
                                            </td>
                                            <td style="text-align: right;">
                                                <input type="number" value="{{ $detail->price }}" min="0" step="1000" class="form-input item-price" style="width: 120px; text-align: right;">
                                            </td>
                                            <td style="text-align: right; font-weight: 600;" class="item-subtotal">
                                                Rp {{ number_format($detail->quantity * $detail->price, 0, ',', '.') }}
                                            </td>
                                            <td style="text-align: center;">
                                                <button onclick="removeLineItem(this)" class="btn btn-sm btn-danger" style="min-width: 32px; padding: 4px 8px;">&times;</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Add Item Button -->
                    <button onclick="addLineItem()" class="btn btn-outline btn-block" style="margin-top: 16px; margin-bottom: 24px; border-style: dashed;">
                        <i class="fas fa-plus"></i> Tambah Item
                    </button>

                    <!-- Total -->
                    <div style="border-top: 1px solid var(--border); padding-top: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 1.1rem; font-weight: 700;">
                            <span>Total</span>
                            <span id="totalPrice" style="color: var(--primary);">Rp {{ number_format($invoice->total_price, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                        <button onclick="previewInvoice()" class="btn btn-outline">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button onclick="saveInvoice()" id="saveBtn" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit Log (Right 1 col) -->
        <div>
            <div class="card glass-card" style="position: sticky; top: 80px;">
                <div class="card-header">
                    <h3 style="font-size: 1rem; font-weight: 600;">
                        <i class="fas fa-history"></i> Riwayat Perubahan
                    </h3>
                </div>
                <div class="card-body">
                    <div id="auditLog" style="max-height: 400px; overflow-y: auto;">
                        @forelse($auditLogs ?? [] as $log)
                            <div style="border-left: 3px solid var(--primary); padding-left: 12px; padding-bottom: 12px; margin-bottom: 12px;">
                                <div style="font-size: 0.875rem; font-weight: 600;">{{ $log->edited_by_name }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $log->edited_by_role }} • {{ $log->created_at->diffForHumans() }}</div>
                                <div style="font-size: 0.8125rem; color: var(--text); margin-top: 6px;">
                                    @if($log->edit_type === 'add_item')
                                        <span style="color: var(--success); font-weight: 600;">+</span> Tambah: {{ $log->new_value['description'] ?? '' }}
                                    @elseif($log->edit_type === 'remove_item')
                                        <span style="color: var(--danger); font-weight: 600;">&times;</span> Hapus: {{ $log->old_value['description'] ?? '' }}
                                    @elseif($log->edit_type === 'update_price')
                                        <span style="color: var(--primary); font-weight: 600;">~</span> Harga: {{ $log->old_value['price'] ?? '' }} → {{ $log->new_value['price'] ?? '' }}
                                    @elseif($log->edit_type === 'update_quantity')
                                        <span style="color: var(--primary); font-weight: 600;">~</span> Qty: {{ $log->old_value['quantity'] ?? '' }} → {{ $log->new_value['quantity'] ?? '' }}
                                    @endif
                                </div>
                                @if($log->notes)
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">{{ $log->notes }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="empty-state" style="padding: 20px 0;">
                                <div class="empty-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <p style="font-size: 0.875rem; color: var(--text-muted);">Belum ada perubahan</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const invoiceId = {{ $invoice->id }};
    const serviceOrderId = {{ $serviceOrder->id }};

    function addLineItem() {
        const tbody = document.getElementById('lineItems');
        const tr = document.createElement('tr');
        tr.className = 'line-item new-item';
        tr.innerHTML = `
            <td>
                <input type="text" value="" class="form-input item-desc" style="width: 100%;" placeholder="Deskripsi item">
            </td>
            <td style="text-align: center;">
                <input type="number" value="1" min="1" class="form-input item-qty" style="width: 70px; text-align: center;">
            </td>
            <td style="text-align: right;">
                <input type="number" value="0" min="0" step="1000" class="form-input item-price" style="width: 120px; text-align: right;">
            </td>
            <td style="text-align: right; font-weight: 600;" class="item-subtotal">Rp 0</td>
            <td style="text-align: center;">
                <button onclick="removeLineItem(this)" class="btn btn-sm btn-danger" style="min-width: 32px; padding: 4px 8px;">&times;</button>
            </td>
        `;
        tbody.appendChild(tr);

        // Add event listeners for auto-calculation
        const inputs = tr.querySelectorAll('input');
        inputs.forEach(input => input.addEventListener('input', () => calculateSubtotal(tr)));
    }

    function showEditorMessage(message, type = 'success') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }

        console[type === 'error' ? 'error' : 'info'](message);
    }

    async function confirmEditorAction(options) {
        if (typeof window.confirmAction !== 'function') {
            return true;
        }

        const result = await window.confirmAction(options);
        return result?.confirmed || result === true;
    }

    async function removeLineItem(btn) {
        const tr = btn.closest('.line-item');
        const description = tr.querySelector('.item-desc')?.value || 'Item invoice';
        const confirmed = await confirmEditorAction({
            type: 'danger',
            heading: 'Hapus item invoice?',
            message: 'Item ini akan dihapus dari draft invoice.',
            confirmText: 'Ya, Hapus',
            details: [{ label: 'Item', value: description }],
        });

        if (!confirmed) {
            return;
        }

        tr.remove();
        calculateTotal();
    }

    function calculateSubtotal(tr) {
        const qty = parseFloat(tr.querySelector('.item-qty').value) || 0;
        const price = parseFloat(tr.querySelector('.item-price').value) || 0;
        const subtotal = qty * price;
        tr.querySelector('.item-subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
        calculateTotal();
    }

    function calculateTotal() {
        const rows = document.querySelectorAll('.line-item');
        let total = 0;
        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            total += qty * price;
        });
        document.getElementById('totalPrice').textContent = 'Rp ' + total.toLocaleString('id-ID');
        return total;
    }

    // Initialize calculation listeners on existing rows
    document.querySelectorAll('.line-item').forEach(tr => {
        tr.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => calculateSubtotal(tr));
        });
    });

    function addTechFeeToInvoice() {
        const description = '{{ addslashes($serviceOrder->technicianAssignment?->fee_description ?? '') }}';
        const amount = {{ $serviceOrder->technicianAssignment?->fee_amount ?? 0 }};

        if (!description || !amount) return;

        const tbody = document.getElementById('lineItems');
        const tr = document.createElement('tr');
        tr.className = 'line-item new-item';
        tr.innerHTML = `
            <td>
                <input type="text" value="${description}" class="form-input item-desc" style="width: 100%;">
            </td>
            <td style="text-align: center;">
                <input type="number" value="1" min="1" class="form-input item-qty" style="width: 70px; text-align: center;">
            </td>
            <td style="text-align: right;">
                <input type="number" value="${amount}" min="0" step="1000" class="form-input item-price" style="width: 120px; text-align: right;">
            </td>
            <td style="text-align: right; font-weight: 600;" class="item-subtotal">Rp ${amount.toLocaleString('id-ID')}</td>
            <td style="text-align: center;">
                <button onclick="removeLineItem(this)" class="btn btn-sm btn-danger" style="min-width: 32px; padding: 4px 8px;">&times;</button>
            </td>
        `;
        tbody.appendChild(tr);

        tr.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => calculateSubtotal(tr));
        });

        calculateTotal();
    }

    async function saveInvoice() {
        const items = [];
        document.querySelectorAll('.line-item').forEach(row => {
            items.push({
                id: row.dataset.id || null,
                description: row.querySelector('.item-desc').value,
                quantity: parseInt(row.querySelector('.item-qty').value) || 1,
                price: parseFloat(row.querySelector('.item-price').value) || 0,
            });
        });

        const confirmed = await confirmEditorAction({
            type: 'success',
            heading: 'Simpan perubahan invoice?',
            message: 'Invoice akan dikirim ke manager untuk approval.',
            confirmText: 'Ya, Simpan',
            details: [
                { label: 'Jumlah Item', value: `${items.length} item` },
                { label: 'Total', value: document.getElementById('totalPrice')?.textContent || '-' },
            ],
        });

        if (!confirmed) {
            return;
        }

        const saveBtn = document.getElementById('saveBtn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

        fetch(`/frontdesk/invoices/${invoiceId}/edit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ items }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showEditorMessage('Invoice berhasil disimpan. Menunggu approval manager.', 'success');
                location.reload();
            } else {
                showEditorMessage(data.message || 'Gagal menyimpan invoice', 'error');
            }
        })
        .catch(() => showEditorMessage('Terjadi kesalahan.', 'error'))
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Perubahan';
        });
    }

    function previewInvoice() {
        window.open(`/invoices/${invoiceId}/print`, '_blank');
    }
</script>

@endsection
