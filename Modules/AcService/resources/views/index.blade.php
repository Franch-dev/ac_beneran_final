@extends('layouts.app')

@section('title', 'AC Service Module')

@section('content')
@php
    $dashboardUrl = auth()->check() ? route('dashboard') : \App\Support\PlatformNavigation::loginUrl('/dashboard');
    $monitoringUrl = auth()->check() ? route('monitoring') : \App\Support\PlatformNavigation::loginUrl('/monitoring');
@endphp
<section class="section">
    <div class="container" style="max-width: 960px;">
        <div class="section-header" style="text-align:left; margin-bottom: 32px;">
            <div class="features-eyebrow">Module Entry</div>
            <h1 style="font-size: clamp(2rem, 5vw, 3.2rem); margin-bottom: 12px;">AC Service</h1>
            <p>Modul operasional utama untuk pengelolaan data masjid, unit AC, monitoring servis, SPK, dan invoice dalam arsitektur modular monolith.</p>
        </div>

        <div class="hero-actions" style="flex-wrap: wrap; gap: 12px; margin-bottom: 24px;">
            <a href="{{ $platformCatalogUrl }}" class="btn btn-outline btn-lg">
                <i class="fas fa-arrow-left"></i> Katalog Platform
            </a>
        </div>

        <div class="catalog-grid">
            <x-catalog-card
                :href="$dashboardUrl"
                :thumbStyle="'background: linear-gradient(135deg, #e8f0fe 0%, #c5d9ff 100%);'"
                :iconStyle="'color: #1a73e8;'"
                iconClass="fas fa-th-large"
                domain="/dashboard"
                tag="Operational"
                title="Dashboard"
                desc="Kelola master data masjid, unit AC, dan operasional utama modul AC Service."
                :footerText="'Buka <i class=\"fas fa-arrow-right\"></i>'"
            />

            <x-catalog-card
                :href="$monitoringUrl"
                :thumbStyle="'background: linear-gradient(135deg, #e6f4ea 0%, #a8d5b5 100%);'"
                :iconStyle="'color: #1e8e3e;'"
                iconClass="fas fa-chart-line"
                domain="/monitoring"
                tag="Workflow"
                title="Monitoring"
                desc="Pantau service order, approval manager, dan progres pelayanan AC secara real-time."
                :footerText="'Buka <i class=\"fas fa-arrow-right\"></i>'"
            />

            <x-catalog-card
                asButton="true"
                :href="null"
                :thumbStyle="'background: linear-gradient(135deg, #fff4e5 0%, #ffe2b8 100%);'"
                :iconStyle="'color: #d97706;'"
                iconClass="fas fa-file-invoice"
                domain="/guest-order"
                tag="Publik"
                title="Ajukan Service Order"
                desc="Kirim permintaan servis AC tanpa login dengan form tamu yang langsung tersambung ke tim operasi."
                footerText="Isi Formulir <i class=\"fas fa-arrow-right\"></i>"
                :buttonAttrs="[
                    'data-guest-order-action' => route('modules.ac-service.guest-order.store'),
                    'data-guest-order-label' => 'AC Service',
                    'onclick' => 'window.openGuestOrderPopup?.(this.dataset.guestOrderAction, this.dataset.guestOrderLabel)'
                ]"
            />
        </div>
</section>

@include('partials.guest-order-popup', [
    'masjids' => $masjids,
    'formActionRoute' => route('modules.ac-service.guest-order.store'),
    'popupTitle' => 'AC Service',
])

@endsection
