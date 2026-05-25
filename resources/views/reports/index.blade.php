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
            <div class="summary-icon bg-primary"><i class="fas fa-money-bill-wave"></i></div>
            <div class="summary-content">
                <div class="summary-num">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                <div class="summary-label">Total Revenue</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon bg-info"><i class="fas fa-file-invoice"></i></div>
            <div class="summary-content">
                <div class="summary-num">{{ $totalInvoices }}</div>
                <div class="summary-label">Total Invoices</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon bg-success"><i class="fas fa-clipboard-list"></i></div>
            <div class="summary-content">
                <div class="summary-num">{{ $totalOrders }}</div>
                <div class="summary-label">Total Orders</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon bg-warning"><i class="fas fa-circle-check"></i></div>
            <div class="summary-content">
                <div class="summary-num">{{ $completedOrders }}</div>
                <div class="summary-label">Completed Orders</div>
            </div>
        </div>
    </div>

    <div class="glass-card" style="margin-bottom:24px">
        <div class="card-header" style="padding:0 0 16px;border-bottom:1px solid var(--border);margin-bottom:16px">
            <h3 style="font-size:1rem;font-weight:600;display:flex;align-items:center;gap:8px">
                <i class="fas fa-chart-pie" style="color:var(--primary)"></i>
                Detail Metrik
            </h3>
        </div>
        <div class="table-container" style="box-shadow:none;border:none;margin-bottom:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th style="text-align:right">Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:36px;height:36px;border-radius:var(--radius-sm);background:var(--warning-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <i class="fas fa-clock" style="color:var(--warning);font-size:0.875rem"></i>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:0.875rem">Pending Orders</div>
                                    <div style="font-size:0.75rem;color:var(--text-muted)">Order menunggu proses</div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align:right"><span class="status-badge status-pending">{{ $pendingOrders }}</span></td>
                    </tr>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:36px;height:36px;border-radius:var(--radius-sm);background:var(--danger-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <i class="fas fa-exclamation-triangle" style="color:var(--danger);font-size:0.875rem"></i>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:0.875rem">Overdue Masjids</div>
                                    <div style="font-size:0.75rem;color:var(--text-muted)">Masjid melewati jadwal servis</div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align:right"><span class="status-badge status-cancelled">{{ $overdueMasjids->count() }}</span></td>
                    </tr>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:36px;height:36px;border-radius:var(--radius-sm);background:var(--primary-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <i class="fas fa-chart-line" style="color:var(--primary);font-size:0.875rem"></i>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:0.875rem">Monthly Revenue Points</div>
                                    <div style="font-size:0.75rem;color:var(--text-muted)">Data point pendapatan bulanan</div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align:right"><strong>{{ $monthlyRevenue->count() }}</strong></td>
                    </tr>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:36px;height:36px;border-radius:var(--radius-sm);background:var(--success-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <i class="fas fa-tags" style="color:var(--success);font-size:0.875rem"></i>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:0.875rem">Revenue by PK Types</div>
                                    <div style="font-size:0.75rem;color:var(--text-muted)">Kategori paket layanan</div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align:right"><strong>{{ $revenueByPK->count() }}</strong></td>
                    </tr>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:36px;height:36px;border-radius:var(--radius-sm);background:var(--info-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <i class="fas fa-mosque" style="color:var(--info);font-size:0.875rem"></i>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:0.875rem">Top Masjids Tracked</div>
                                    <div style="font-size:0.75rem;color:var(--text-muted)">Masjid dengan aktivitas tertinggi</div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align:right"><strong>{{ $topMasjids->count() }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
