@extends('layouts.app')

@section('title', 'Viewer Dashboard - AC Servis Masjid')

@section('content')
<div id="viewerSyncRoot">
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-eye"></i> Viewer Dashboard</h1>
            <p class="page-subtitle">Ringkasan data untuk audit dan pemantauan</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-secondary" type="button" onclick="manualRefreshViewer()">
                <i class="fas fa-rotate-right"></i> Refresh Data
            </button>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-icon bg-info">
                <i class="fas fa-mosque"></i>
            </div>
            <div>
                <div class="summary-num">{{ $totalMasjid }}</div>
                <div class="summary-label">Masjid/Musholla</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon bg-primary">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div>
                <div class="summary-num">{{ $totalOrders }}</div>
                <div class="summary-label">Total Orders</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon bg-danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <div class="summary-num">{{ $overdueMasjids }}</div>
                <div class="summary-label">Overdue</div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="card-header" style="padding: 16px 20px; border-bottom: 1px solid var(--border);">
            <h3 style="font-size: 0.9375rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-clock" style="color: var(--primary);"></i> Order Terbaru
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Masjid</th>
                        <th>Tanggal Service</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                    <tr>
                        <td>
                            <span class="order-num">{{ $order->order_number }}</span>
                        </td>
                        <td>
                            <div style="font-weight: 500;">{{ $order->masjid?->name }}</div>
                            @if($order->masjid?->address)
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ Str::limit($order->masjid->address, 40) }}</div>
                            @endif
                        </td>
                        <td>
                            <div style="font-size: 0.8125rem;">{{ $order->service_date?->format('d M Y') }}</div>
                        </td>
                        <td>
                            @php
                                $statusLabels = \App\Models\ServiceOrder::STATUS_LABELS;
                                $label = $statusLabels[$order->status] ?? ucfirst($order->status);
                                $isCompleted = in_array($order->status, ['completed', 'cancelled']);
                                $isActive = in_array($order->status, [
                                    'pending_review',
                                    'approved',
                                    'spk_invoice_created',
                                    'waiting_payment',
                                    'payment_verified',
                                    'technician_assigned',
                                    'in_progress',
                                    'waiting_review',
                                ]);
                            @endphp
                            @if(in_array($order->status, ['pending_review', 'approved', 'spk_invoice_created'], true))
                                <span class="status-badge status-pending"><i class="fas fa-file-alt"></i> {{ $label }}</span>
                            @elseif($isActive)
                                <span class="status-badge" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-spinner"></i> {{ $label }}</span>
                            @elseif($isCompleted)
                                <span class="status-badge" style="background: var(--success-bg); color: var(--success);"><i class="fas fa-check"></i> {{ $label }}</span>
                            @else
                                <span class="status-badge" style="background: var(--warning-bg); color: var(--warning);"><i class="fas fa-clock"></i> {{ $label }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                                <h3>Belum ada order</h3>
                                <p>Order service akan muncul di sini</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
window.PAGE_SYNC_CONFIG = {
    rootSelector: '#viewerSyncRoot',
    snapshotRoute: '{{ route("viewer.snapshot") }}',
};

function manualRefreshViewer() {
    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-sync fa-spin"></i> Memuat...';
    btn.disabled = true;

    window.refreshCurrentPageSnapshot()
        .then(() => {
            showToast('Data berhasil diperbarui!', 'success');
        })
        .catch((error) => {
            showToast('Gagal memperbarui data: ' + error.message, 'error');
            console.error('Viewer dashboard refresh failed:', error);
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
}
</script>
@endpush
