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
            <div class="summary-content">
                <div class="summary-num">{{ $active->count() }}</div>
                <div class="summary-label">Active Assignments</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-content">
                <div class="summary-num">{{ $completed->count() }}</div>
                <div class="summary-label">Completed Assignments</div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Masjid</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($active as $assignment)
                    <tr>
                        <td>{{ $assignment->serviceOrder?->order_number }}</td>
                        <td>{{ $assignment->serviceOrder?->masjid?->name }}</td>
                        <td>{{ $assignment->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Tidak ada tugas aktif.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
window.PAGE_SYNC_CONFIG = {
    rootSelector: '#technicianSyncRoot',
    snapshotRoute: '{{ route("technician.snapshot") }}',
};

// Manual refresh function for technician dashboard (replaces auto-sync)
function manualRefreshTechnician() {
    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-sync fa-spin"></i> Memuat...';
    btn.disabled = true;

    // Trigger manual snapshot refresh
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
@endpush
