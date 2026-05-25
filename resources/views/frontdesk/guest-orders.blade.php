@extends('layouts.app')

@section('title', 'Guest Orders - AC Beneran')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-inbox"></i> Guest Orders</h1>
            <p class="page-subtitle">Kelola permintaan service dari pelanggan yang belum terdaftar.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('monitoring') }}" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Kembali ke Monitoring
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <div class="summary-num">{{ $guestOrders->where('status', 'pending_review')->count() }}</div>
                <div class="summary-label">Pending Review</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div class="summary-num">{{ $guestOrders->where('status', 'approved')->count() }}</div>
                <div class="summary-label">Disetujui</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon bg-danger">
                <i class="fas fa-times-circle"></i>
            </div>
            <div>
                <div class="summary-num">{{ $guestOrders->where('status', 'rejected')->count() }}</div>
                <div class="summary-label">Ditolak</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon bg-info">
                <i class="fas fa-list"></i>
            </div>
            <div>
                <div class="summary-num">{{ $guestOrders->total() }}</div>
                <div class="summary-label">Total Orders</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card glass-card">
        <div class="card-body">
            <form action="{{ route('frontdesk.guest-orders') }}" method="GET" class="search-form">
                <div class="search-input-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="search-input" placeholder="Cari nama, telepon, masjid...">
                </div>
                <select name="status" class="form-select" style="width: auto; min-width: 180px;">
                    <option value="">Semua Status</option>
                    <option value="pending_review" {{ request('status') == 'pending_review' ? 'selected' : '' }}>Pending Review</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Pending Alert Banner -->
    @php
        $pendingCount = $guestOrders->where('status', 'pending_review')->count();
    @endphp
    @if($pendingCount > 0)
    <div class="alert alert-warning glass-card" style="display: flex; align-items: center; gap: 12px; padding: 14px 18px; margin-bottom: 16px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 1.25rem;"></i>
        <span style="font-weight: 600;">{{ $pendingCount }} order menunggu review</span>
    </div>
    @endif

    <!-- Guest Orders Table -->
    <div class="table-container">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pelanggan</th>
                        <th>Masjid/Musholla</th>
                        <th>AC</th>
                        <th>Detail Unit</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guestOrders as $order)
                    <tr id="order-{{ $order->id }}">
                        <td>
                            <div style="font-weight: 600; font-size: 0.875rem;">{{ $order->guest_name }}</div>
                            <div style="font-size: 0.8125rem; color: var(--text-muted);">{{ $order->guest_phone }}</div>
                            @if($order->additional_phone_description)
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                                <i class="fas fa-info-circle"></i> {{ $order->additional_phone_description }}
                            </div>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 500; font-size: 0.875rem;">{{ $order->masjid?->name ?? $order->masjid_name }}</div>
                            @if($order->address)
                            <div style="font-size: 0.8125rem; color: var(--text-muted);">{{ Str::limit($order->address, 50) }}</div>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 600; font-size: 0.875rem;">{{ $order->ac_amount }} unit</div>
                            <div style="font-size: 0.8125rem; color: var(--text-muted);">{{ $order->ac_type }}</div>
                        </td>
                        <td>
                            <div style="max-width: 200px; font-size: 0.8125rem; color: var(--text);">{{ Str::limit($order->problem_description, 80) }}</div>
                        </td>
                        <td>
                            @if($order->status === 'pending_review')
                                <span class="status-badge status-pending">
                                    <i class="fas fa-clock"></i> Pending Review
                                </span>
                            @elseif($order->status === 'approved')
                                <span class="status-badge" style="background: var(--success-bg); color: var(--success);">
                                    <i class="fas fa-check"></i> Disetujui
                                </span>
                            @elseif($order->status === 'rejected')
                                <span class="status-badge status-cancelled">
                                    <i class="fas fa-times"></i> Ditolak
                                </span>
                            @else
                                <span class="status-badge" style="background: var(--gray-100); color: var(--text-muted);">
                                    {{ ucfirst($order->status) }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div style="font-size: 0.8125rem; color: var(--text-muted);">
                                {{ $order->created_at->format('d M Y') }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                {{ $order->created_at->format('H:i') }}
                            </div>
                        </td>
                        <td>
                            <div class="action-btns">
                                @if($order->status === 'pending_review')
                                <a href="{{ route('frontdesk.guest-orders.show', $order->id) }}" class="btn btn-sm btn-primary" style="min-height: 32px;">
                                    <i class="fas fa-eye"></i> Review
                                </a>
                                @elseif($order->status === 'rejected')
                                <span class="text-xs text-muted" style="font-size: 0.75rem; max-width: 150px; display: block;" title="{{ $order->rejection_reason }}">
                                    <i class="fas fa-comment"></i> {{ Str::limit($order->rejection_reason, 30) }}
                                </span>
                                @else
                                <span style="font-size: 0.75rem; color: var(--text-muted);">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <h3>Tidak ada guest orders</h3>
                                <p>Belum ada permintaan service dari pelanggan</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($guestOrders->hasPages())
    <div style="margin-top: 20px;">
        {{ $guestOrders->links() }}
    </div>
    @endif
</div>

@endsection
