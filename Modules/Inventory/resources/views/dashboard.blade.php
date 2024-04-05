@extends('layouts.app')

@section('title', 'Inventory Dashboard')

@section('content')
<section class="section">
    <div class="container" style="max-width: 1080px;">
        <div class="section-header" style="text-align:left; margin-bottom: 28px;">
            <div class="features-eyebrow">Inventory Dashboard</div>
            <h1 style="font-size: clamp(1.9rem, 4vw, 3rem); margin-bottom: 12px;">Inventory Overview</h1>
            <p>Read-only MVP untuk memantau aset servis, stok inti, dan kategori inventaris yang siap dipakai tim operasi.</p>
        </div>

        <div class="pricing-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 28px;">
            <div class="pricing-card glass-card">
                <div class="pricing-header">
                    <span class="pricing-pk">Assets</span>
                    <h3>{{ $summary['totalAssets'] }}</h3>
                </div>
                <p class="text-muted">Jumlah baris aset yang sedang dipantau.</p>
            </div>
            <div class="pricing-card glass-card">
                <div class="pricing-header">
                    <span class="pricing-pk">Quantity</span>
                    <h3>{{ $summary['totalQuantity'] }}</h3>
                </div>
                <p class="text-muted">Akumulasi unit dari seluruh item inventaris.</p>
            </div>
            <div class="pricing-card glass-card">
                <div class="pricing-header">
                    <span class="pricing-pk">Categories</span>
                    <h3>{{ $summary['categories'] }}</h3>
                </div>
                <p class="text-muted">Kategori inventaris yang saat ini dilacak.</p>
            </div>
            <div class="pricing-card glass-card">
                <div class="pricing-header">
                    <span class="pricing-pk">Low Stock</span>
                    <h3>{{ $summary['lowStock'] }}</h3>
                </div>
                <p class="text-muted">Item yang butuh perhatian pengadaan.</p>
            </div>
        </div>

        <div class="table-container glass-card" style="padding: 0; overflow: hidden;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Qty</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assets as $asset)
                        <tr>
                            <td>{{ $asset['code'] }}</td>
                            <td>{{ $asset['name'] }}</td>
                            <td>{{ $asset['category'] }}</td>
                            <td>{{ $asset['location'] }}</td>
                            <td>{{ $asset['quantity'] }}</td>
                            <td>
                                <span class="status-badge status-{{ $asset['status'] === 'low' ? 'pending' : ($asset['status'] === 'scheduled' ? 'waiting_review' : 'approved') }}">
                                    {{ ucfirst($asset['status']) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
