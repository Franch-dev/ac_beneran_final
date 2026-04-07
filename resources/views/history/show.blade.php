@extends('layouts.app')

@section('title', 'Masjid History - AC Servis Masjid')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-history"></i> {{ $masjid->name }}</h1>
            <p class="page-subtitle">Riwayat servis dan invoice</p>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-content">
                <div class="summary-num">{{ $totalServices }}</div>
                <div class="summary-label">Completed Services</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-content">
                <div class="summary-num">{{ number_format($totalRevenue, 0, ',', '.') }}</div>
                <div class="summary-label">Revenue</div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Invoice</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->service_date->format('d M Y') }}</td>
                        <td>{{ $order->status }}</td>
                        <td>{{ $order->invoice?->invoice_number ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Belum ada riwayat servis.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $orders->links() }}
</div>
@endsection
