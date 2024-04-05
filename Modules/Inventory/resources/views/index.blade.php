@extends('layouts.app')

@section('title', 'Inventory Module')

@section('content')
<section class="section">
    <div class="container" style="max-width: 960px;">
        <div class="section-header" style="text-align:left; margin-bottom: 24px;">
            <div class="features-eyebrow">Inventory MVP</div>
            <h1 style="font-size: clamp(2rem, 5vw, 3rem); margin-bottom: 12px;">Inventory &amp; Asset Tracking</h1>
            <p>Hub inventaris ringan untuk melihat stok kerja, aset servis, dan perlengkapan operasional lintas tim tanpa meninggalkan katalog utama.</p>
        </div>

        <div class="info-banner" style="margin-bottom: 24px;">
            <i class="fas fa-boxes-stacked"></i>
            Ringkasan saat ini:
            <strong>{{ $summary['totalAssets'] }}</strong> aset aktif,
            <strong>{{ $summary['totalQuantity'] }}</strong> item total,
            <strong>{{ $summary['categories'] }}</strong> kategori inventaris.
        </div>

        <div class="hero-actions" style="flex-wrap: wrap; gap: 12px;">
            <a href="{{ $dashboardUrl }}" class="btn btn-primary btn-lg">
                <i class="fas fa-warehouse"></i> Dashboard inventaris
            </a>
            <a href="{{ $platformCatalogUrl }}" class="btn btn-outline btn-lg">
                <i class="fas fa-arrow-left"></i> Katalog Platform
            </a>
        </div>

        <div class="catalog-grid" style="margin-top: 32px;">
            <div class="catalog-card glass-card">
                <div class="catalog-card-body">
                    <div class="catalog-card-meta">
                        <span class="catalog-domain">Read-only slice</span>
                        <span class="catalog-tag">Ready</span>
                    </div>
                    <h3 class="catalog-card-title">Asset overview</h3>
                    <p class="catalog-card-desc">Daftar aset inti sudah tersedia di dashboard untuk memvalidasi alur login, navigasi, dan presentasi data modul.</p>
                </div>
            </div>
            <div class="catalog-card glass-card">
                <div class="catalog-card-body">
                    <div class="catalog-card-meta">
                        <span class="catalog-domain">Shared auth</span>
                        <span class="catalog-tag">Integrated</span>
                    </div>
                    <h3 class="catalog-card-title">Catalog-first flow</h3>
                    <p class="catalog-card-desc">Pengguna kembali ke katalog utama saat logout, lalu masuk lagi ke dashboard inventaris lewat shared login yang sama.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
