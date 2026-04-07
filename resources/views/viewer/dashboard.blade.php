@extends('layouts.app')

@section('title', 'Viewer Dashboard - AC Servis Masjid')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-eye"></i> Viewer Dashboard</h1>
            <p class="page-subtitle">Ringkasan data untuk audit dan pemantauan</p>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-content">
                <div class="summary-num">{{ $totalMasjid }}</div>
                <div class="summary-label">Masjids</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-content">
                <div class="summary-num">{{ $totalOrders }}</div>
                <div class="summary-label">Orders</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-content">
                <div class="summary-num">{{ $overdueMasjids }}</div>
                <div class="summary-label">Overdue Masjids</div>
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
                @forelse($recentOrders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->masjid?->name }}</td>
                        <td>{{ $order->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Belum ada order terbaru.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
