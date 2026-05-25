@extends('layouts.app')

@section('title', 'Daftar Tanda Terima - AC Beneran')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-receipt"></i> Daftar Tanda Terima</h1>
            <p class="page-subtitle">Semua tanda terima pembayaran</p>
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
        <form action="{{ route('manager.receipts') }}" method="GET" class="search-form">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="search-input" placeholder="Cari nomor tanda terima, order, masjid...">
            </div>
            <select name="method" class="form-select" style="width: auto; min-width: 180px;">
                <option value="">Semua Metode</option>
                <option value="cash" {{ request('method') == 'cash' ? 'selected' : '' }}>Tunai</option>
                <option value="transfer" {{ request('method') == 'transfer' ? 'selected' : '' }}>Transfer</option>
                <option value="qris" {{ request('method') == 'qris' ? 'selected' : '' }}>QRIS</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div>

    <!-- Receipts Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No. Tanda Terima</th>
                    <th>Order</th>
                    <th>Masjid</th>
                    <th>Jumlah</th>
                    <th>Metode</th>
                    <th>Tanggal</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipts as $receipt)
                    <tr>
                        <td>
                            <div style="font-weight: 600;">{{ $receipt->receipt_number }}</div>
                        </td>
                        <td>
                            <div class="order-num">#{{ $receipt->serviceOrder->order_number }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">{{ $receipt->serviceOrder->masjid->name }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $receipt->formatted_amount }}</div>
                        </td>
                        <td>
                            @if($receipt->payment_method === 'cash')
                                <span class="status-badge status-approved">Tunai</span>
                            @elseif($receipt->payment_method === 'transfer')
                                <span class="status-badge" style="background: var(--info-soft); color: var(--info);">Transfer</span>
                            @else
                                <span class="status-badge" style="background: var(--primary-soft); color: var(--primary);">QRIS</span>
                            @endif
                        </td>
                        <td style="color: var(--text-muted);">
                            {{ $receipt->payment_date->format('d M Y') }}
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('manager.receipts.show', $receipt->id) }}" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> Lihat / Cetak</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state" style="padding: 48px 24px;">
                            <div class="empty-icon" style="margin: 0 auto 16px;"><i class="fas fa-receipt"></i></div>
                            <h3>Belum ada tanda terima</h3>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($receipts->hasPages())
            <div style="padding: 16px;">{{ $receipts->links() }}</div>
        @endif
    </div>
</div>
@endsection
