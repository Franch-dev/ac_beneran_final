@extends('layouts.app')

@section('title', 'Reports - AC Servis Masjid')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-chart-bar"></i> Reports</h1>
            <p class="page-subtitle">Ringkasan performa layanan dan pendapatan</p>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-content">
                <div class="summary-num">{{ number_format($totalRevenue, 0, ',', '.') }}</div>
                <div class="summary-label">Total Revenue</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-content">
                <div class="summary-num">{{ $totalInvoices }}</div>
                <div class="summary-label">Total Invoices</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-content">
                <div class="summary-num">{{ $totalOrders }}</div>
                <div class="summary-label">Total Orders</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-content">
                <div class="summary-num">{{ $completedOrders }}</div>
                <div class="summary-label">Completed Orders</div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Pending Orders</td>
                    <td>{{ $pendingOrders }}</td>
                </tr>
                <tr>
                    <td>Overdue Masjids</td>
                    <td>{{ $overdueMasjids->count() }}</td>
                </tr>
                <tr>
                    <td>Monthly Revenue Points</td>
                    <td>{{ $monthlyRevenue->count() }}</td>
                </tr>
                <tr>
                    <td>Revenue by PK Types</td>
                    <td>{{ $revenueByPK->count() }}</td>
                </tr>
                <tr>
                    <td>Top Masjids Tracked</td>
                    <td>{{ $topMasjids->count() }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
