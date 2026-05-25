@extends('layouts.app')

@section('title', 'Approval Biaya Tambahan - AC Beneran')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-check-circle"></i> Approval Biaya Tambahan</h1>
            <p class="page-subtitle">Review dan setujui biaya tambahan dari teknisi</p>
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
        <form action="{{ route('manager.approvals') }}" method="GET" class="search-form">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="search-input" placeholder="Cari order number, masjid...">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div>

    <!-- Orders List -->
    <div class="space-y-6">
        @forelse($orders as $order)
            <div class="glass-card" id="order-{{ $order->id }}" style="margin-bottom: 24px;">
                <!-- Order Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--text);">#{{ $order->order_number }}</h3>
                        <p style="color: var(--text-muted);">{{ $order->masjid->name }}</p>
                    </div>
                    <span class="status-badge status-pending">
                        <span class="urgency-pulse urgency-harus_servis"></span>
                        Menunggu Approval
                    </span>
                </div>

                <!-- Technician Fee Report -->
                <div class="lg-card lg-inset" style="margin-bottom: 16px;">
                    <h4 style="font-weight: 600; color: var(--text); margin-bottom: 12px;">Laporan Biaya Tambahan dari Teknisi</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 0.875rem;">
                        <div>
                            <span style="color: var(--text-muted);">Teknisi:</span>
                            <span style="margin-left: 8px; font-weight: 600;">{{ $order->technicianAssignment?->technician_name }}</span>
                        </div>
                        <div>
                            <span style="color: var(--text-muted);">Jumlah Biaya:</span>
                            <span style="margin-left: 8px; font-weight: 600; color: var(--danger);">Rp {{ number_format($order->technicianAssignment?->fee_amount ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <span style="color: var(--text-muted);">Deskripsi:</span>
                            <span style="margin-left: 8px;">{{ $order->technicianAssignment?->fee_description }}</span>
                        </div>
                        @if($order->technicianAssignment?->fee_tools_materials)
                            <div style="grid-column: 1 / -1;">
                                <span style="color: var(--text-muted);">Alat/Bahan:</span>
                                <span style="margin-left: 8px;">{{ $order->technicianAssignment?->fee_tools_materials }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Current Invoice -->
                <div class="lg-card" style="margin-bottom: 16px; background: var(--primary-soft); border-color: var(--primary-mid);">
                    <h4 style="font-weight: 600; color: var(--primary); margin-bottom: 8px;">Invoice Saat Ini</h4>
                    <div style="font-size: 0.875rem;">
                        <span style="color: var(--text-muted);">Invoice #:</span>
                        <span style="margin-left: 8px; font-weight: 600;">{{ $order->invoice?->invoice_number ?? 'Belum ada' }}</span>
                        <span style="margin-left: 16px; color: var(--text-muted);">Total:</span>
                        <span style="margin-left: 8px; font-weight: 600;">Rp {{ number_format($order->invoice?->total_price ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">
                    <button onclick="showRejectModal({{ $order->id }})" class="btn btn-danger"><i class="fas fa-times"></i> Tolak</button>
                    <button onclick="approveOrder({{ $order->id }})" class="btn btn-success"><i class="fas fa-check"></i> Setujui Biaya</button>
                </div>
            </div>
        @empty
            <div class="empty-state" style="grid-column: unset; padding: 64px 24px; text-align: center;">
                <div class="empty-icon"><i class="fas fa-check-circle"></i></div>
                <h3>Tidak ada approval pending</h3>
                <p>Semua biaya tambahan sudah diproses</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($orders->hasPages())
        <div style="margin-top: 24px;">
            {{ $orders->links() }}
        </div>
    @endif

</div>

<!-- Reject Modal -->
<div class="overlay" id="rejectModal" style="z-index: 2000;">
    <div class="popup active" style="max-width: 480px;">
        <div class="popup-header">
            <h3><i class="fas fa-times-circle"></i> Tolak Biaya Tambahan</h3>
            <button class="popup-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <div class="popup-body">
            <form id="rejectForm" method="POST">
                @csrf
                <div class="form-group">
                    <label for="rejection_reason" class="form-label">Alasan Penolakan <span class="required">*</span></label>
                    <textarea name="rejection_reason" id="rejection_reason" rows="4" required class="form-textarea" placeholder="Jelaskan alasan penolakan..."></textarea>
                </div>
                <div class="popup-actions">
                    <button type="button" onclick="closeRejectModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentOrderId = null;

    function approveOrder(orderId) {
        if (typeof window.openConfirmModal === 'function') {
            window.openConfirmModal({
                type: 'success',
                heading: 'Setujui Biaya Tambahan?',
                message: 'Order akan siap untuk pembayaran setelah disetujui.',
                confirmText: 'Ya, Setujui',
                onConfirm: () => {
                    fetch(`/manager/approvals/${orderId}/approve`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const row = document.getElementById(`order-${orderId}`);
                            if (row) row.remove();
                            if (typeof window.showToast === 'function') {
                                window.showToast(data.message, 'success');
                            } else {
                                alert(data.message);
                            }
                            location.reload();
                        } else {
                            if (typeof window.showToast === 'function') {
                                window.showToast(data.message || 'Gagal approve', 'error');
                            } else {
                                alert(data.message || 'Gagal approve');
                            }
                        }
                    })
                    .catch(() => {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Terjadi kesalahan.', 'error');
                        } else {
                            alert('Terjadi kesalahan.');
                        }
                    });
                }
            });
        } else {
            if (!confirm('Setujui biaya tambahan ini? Order akan siap untuk pembayaran.')) return;
            fetch(`/manager/approvals/${orderId}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById(`order-${orderId}`);
                    if (row) row.remove();
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Gagal approve');
                }
            })
            .catch(() => alert('Terjadi kesalahan.'));
        }
    }

    function showRejectModal(orderId) {
        currentOrderId = orderId;
        const modal = document.getElementById('rejectModal');
        modal.classList.add('active');
        modal.style.display = 'block';
        document.getElementById('rejection_reason').focus();
    }

    function closeRejectModal() {
        currentOrderId = null;
        const modal = document.getElementById('rejectModal');
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.getElementById('rejection_reason').value = '';
    }

    document.getElementById('rejectForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!currentOrderId) return;

        const reason = document.getElementById('rejection_reason').value.trim();
        if (!reason) { alert('Alasan penolakan wajib diisi'); return; }

        fetch(`/manager/approvals/${currentOrderId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ rejection_reason: reason }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeRejectModal();
                alert(data.message);
                location.reload();
            } else {
                alert(data.message || 'Gagal menolak');
            }
        })
        .catch(() => alert('Terjadi kesalahan.'));
    });
</script>
@endsection
