@extends('layouts.app')

@section('title', 'Technician Dashboard - AC Servis Masjid')

@section('content')
<div id="technicianSyncRoot">
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-tools"></i> Technician Dashboard</h1>
            <p class="page-subtitle">Tugas aktif dan riwayat penyelesaian</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-secondary" type="button" onclick="manualRefreshTechnician()">
                <i class="fas fa-rotate-right"></i> Refresh Data
            </button>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-icon bg-primary">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div>
                <div class="summary-num">{{ $active->count() }}</div>
                <div class="summary-label">Tugas Aktif</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div class="summary-num">{{ $completed->count() }}</div>
                <div class="summary-label">Selesai</div>
            </div>
        </div>
    </div>

    <!-- Active Assignments -->
    <div class="table-container">
        <div class="card-header" style="padding: 16px 20px; border-bottom: 1px solid var(--border);">
            <h3 style="font-size: 0.9375rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt" style="color: var(--primary);"></i> Tugas Aktif
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
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($active as $assignment)
                    <tr>
                        <td>
                            <span class="order-num">{{ $assignment->serviceOrder?->order_number }}</span>
                        </td>
                        <td>
                            <div style="font-weight: 500;">{{ $assignment->serviceOrder?->masjid?->name }}</div>
                            @if($assignment->serviceOrder?->masjid?->address)
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ Str::limit($assignment->serviceOrder->masjid->address, 40) }}</div>
                            @endif
                        </td>
                        <td>
                            <div style="font-size: 0.8125rem;">{{ $assignment->serviceOrder?->service_date?->format('d M Y') }}</div>
                        </td>
                        <td>
                            @php
                                $status = $assignment->status;
                                $statusClass = match($status) {
                                    'assigned' => 'status-pending',
                                    'in_progress' => 'status-badge',
                                    'waiting_review' => 'status-badge',
                                    default => 'status-badge',
                                };
                                $statusLabel = match($status) {
                                    'assigned' => 'Ditugaskan',
                                    'in_progress' => 'Dikerjakan',
                                    'waiting_review' => 'Menunggu Review',
                                    default => ucfirst($status),
                                };
                                $statusColor = match($status) {
                                    'assigned' => '',
                                    'in_progress' => 'style="background: var(--primary-soft); color: var(--primary);"',
                                    'waiting_review' => 'style="background: var(--warning-bg); color: var(--warning);"',
                                    default => '',
                                };
                            @endphp
                            <span class="status-badge {{ $statusClass }}" {!! $statusColor !!}>
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="{{ route('technician.spk', $assignment->serviceOrder) }}" class="btn btn-sm btn-outline">
                                    <i class="fas fa-file-alt"></i> SPK
                                </a>
                                <a href="{{ route('technician.invoice', $assignment->serviceOrder) }}" class="btn btn-sm btn-outline">
                                    <i class="fas fa-receipt"></i> Invoice
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fas fa-clipboard-check"></i></div>
                                <h3>Tidak ada tugas aktif</h3>
                                <p>Semua pekerjaan telah diselesaikan</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Completed Assignments -->
    @if($completed->count() > 0)
    <div class="table-container" style="margin-top: 24px;">
        <div class="card-header" style="padding: 16px 20px; border-bottom: 1px solid var(--border);">
            <h3 style="font-size: 0.9375rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-check-double" style="color: var(--success);"></i> Riwayat Selesai
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Masjid</th>
                        <th>Tanggal Selesai</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($completed as $assignment)
                    <tr>
                        <td>
                            <span class="order-num">{{ $assignment->serviceOrder?->order_number }}</span>
                        </td>
                        <td>
                            <div style="font-weight: 500;">{{ $assignment->serviceOrder?->masjid?->name }}</div>
                        </td>
                        <td>
                            <div style="font-size: 0.8125rem;">{{ $assignment->updated_at?->format('d M Y') }}</div>
                        </td>
                        <td>
                            <span class="status-badge" style="background: var(--success-bg); color: var(--success);">
                                <i class="fas fa-check"></i> Selesai
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
</div>
@endsection

@push('scripts')
<script>
window.PAGE_SYNC_CONFIG = {
    rootSelector: '#technicianSyncRoot',
    snapshotRoute: '{{ route("technician.snapshot") }}',
};

function manualRefreshTechnician() {
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
            console.error('Technician dashboard refresh failed:', error);
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
}
</script>
@endpush
