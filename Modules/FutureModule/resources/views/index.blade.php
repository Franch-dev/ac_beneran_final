@extends('layouts.app')

@section('title', 'Future Module')

@section('content')
<section class="section">
    <div class="container" style="max-width: 960px;">
        <div class="section-header" style="text-align:left; margin-bottom: 24px;">
            <div class="features-eyebrow">Reusable Template</div>
            <h1 style="font-size: clamp(2rem, 5vw, 3rem); margin-bottom: 12px;">Future Module Workspace</h1>
            <p>Template kerja untuk modul baru agar setiap mini-website berikutnya langsung mengikuti kontrak route, auth, dan katalog yang sama.</p>
        </div>

        <div class="info-banner" style="margin-bottom: 24px;">
            <i class="fas fa-rocket"></i>
            Saat ini ada
            <strong>{{ $summary['totalTracks'] }}</strong> track kesiapan,
            <strong>{{ $summary['readyTracks'] }}</strong> siap dipakai,
            <strong>{{ $summary['queuedTracks'] }}</strong> menunggu domain problem statement berikutnya.
        </div>

        <div class="hero-actions" style="flex-wrap: wrap; gap: 12px;">
            <a href="{{ $dashboardUrl }}" class="btn btn-primary btn-lg">
                <i class="fas fa-diagram-project"></i> Buka workspace
            </a>
            <a href="{{ $platformCatalogUrl }}" class="btn btn-outline btn-lg">
                <i class="fas fa-arrow-left"></i> Katalog Platform
            </a>
        </div>

        <div class="catalog-grid" style="margin-top: 32px;">
            <div class="catalog-card glass-card">
                <div class="catalog-card-body">
                    <div class="catalog-card-meta">
                        <span class="catalog-domain">Template-ready</span>
                        <span class="catalog-tag">Reusable</span>
                    </div>
                    <h3 class="catalog-card-title">Launch checklist</h3>
                    <p class="catalog-card-desc">Workspace ini menyimpan track kesiapan modul baru supaya tim berikutnya tidak mengulang setup auth, layout, dan katalog.</p>
                </div>
            </div>
            <div class="catalog-card glass-card">
                <div class="catalog-card-body">
                    <div class="catalog-card-meta">
                        <span class="catalog-domain">No dead ends</span>
                        <span class="catalog-tag">Honest</span>
                    </div>
                    <h3 class="catalog-card-title">Clear status</h3>
                    <p class="catalog-card-desc">Daripada placeholder kosong, halaman ini sekarang menjelaskan apa yang sudah siap dan bagian mana yang masih menunggu keputusan produk.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
