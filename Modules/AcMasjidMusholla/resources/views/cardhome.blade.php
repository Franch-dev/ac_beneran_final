@extends('layouts.app')

@section('title', 'AC Masjid & Musholla � Forkis')

@section('content')
<section class="section">
    <div class="container" style="max-width: 920px;">
        <div class="section-header" style="text-align:left; margin-bottom: 28px;">
            <div class="features-eyebrow">Layanan Publik</div>
            <h1 style="font-size: clamp(1.75rem, 4vw, 2.75rem); margin-bottom: 12px;">AC Masjid &amp; Musholla</h1>
            <p>Modul informasi layanan, harga infaq servis, dan pantauan unit AC untuk masjlid dan musholla dalam jejaring Forkis. Dashboard dan monitoring membutuhkan login.</p>
        </div>

        <div class="info-banner" style="margin-bottom: 24px;">
            <i class="fas fa-mosque"></i>
            Ringkasan saat ini: <strong>{{ $totalMasjid }}</strong> masjid/musholla tercatat,
            <strong>{{ $totalUnit }}</strong> unit AC terkelola (sumber data operasional utama).
        </div>

        <div class="hero-actions" style="flex-wrap: wrap; gap: 12px;">
            <a href="{{ $dashboardUrl }}" class="btn btn-primary btn-lg">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="{{ $monitoringUrl }}" class="btn btn-outline btn-lg">
                <i class="fas fa-chart-line"></i> Monitoring
            </a>
            <button
                type="button"
                class="btn btn-secondary btn-lg"
                data-guest-order-action="{{ route('modules.ac-masjid-musholla.guest-order.store') }}"
                data-guest-order-label="AC Masjid & Musholla"
                onclick='window.openGuestOrderPopup?.(this.dataset.guestOrderAction, this.dataset.guestOrderLabel)'
            >
                <i class="fas fa-file-invoice"></i> Ajukan Service Order
            </button>
            <a href="{{ $landingpageUrl }}" class="btn btn-outline btn-lg">
                <i class="fas fa-arrow-left"></i> Katalog Platform
            </a>
        </div>
    </div>
</section>

@include('partials.guest-order-popup', [
    'masjids' => $masjids,
    'formActionRoute' => route('modules.ac-masjid-musholla.guest-order.store'),
    'popupTitle' => 'AC Masjid & Musholla',
])

<section class="section section-alt" id="harga">
    <div class="container" style="max-width: 920px;">
        <div class="section-header" style="text-align:left;">
            <h2>Harga referensi servis</h2>
            <p>Selaras dengan paket di landing Forkis; detail final mengikuti konfirmasi teknisi.</p>
        </div>
        <div class="pricing-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div class="pricing-card glass-card">
                <div class="pricing-header">
                    <span class="pricing-pk">1 PK</span>
                    <h3>Standar</h3>
                </div>
                <div class="pricing-price">
                    <span class="price">Rp 40.000</span>
                    <span class="price-unit">/ unit</span>
                </div>
            </div>
            <div class="pricing-card glass-card pricing-featured">
                <div class="pricing-badge">Umum</div>
                <div class="pricing-header">
                    <span class="pricing-pk">2 PK</span>
                    <h3>Premium</h3>
                </div>
                <div class="pricing-price">
                    <span class="price">Rp 45.000</span>
                    <span class="price-unit">/ unit</span>
                </div>
            </div>
            <div class="pricing-card glass-card">
                <div class="pricing-header">
                    <span class="pricing-pk">5 PK</span>
                    <h3>Besar</h3>
                </div>
                <div class="pricing-price">
                    <span class="price">Rp 80.000</span>
                    <span class="price-unit">/ unit</span>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
