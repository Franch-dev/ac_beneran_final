@extends('layouts.app')

@section('title', 'AC Service Module')

@section('content')
<section class="section">
    <div class="container" style="max-width: 960px;">
        <div class="section-header" style="text-align:left; margin-bottom: 32px;">
            <div class="features-eyebrow">Module Entry</div>
            <h1 style="font-size: clamp(2rem, 5vw, 3.2rem); margin-bottom: 12px;">AC Service</h1>
            <p>Modul operasional utama untuk pengelolaan data masjid, unit AC, monitoring servis, SPK, dan invoice dalam arsitektur modular monolith.</p>
        </div>

        <div class="catalog-grid">
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="catalog-card">
                <div class="catalog-card-thumb" style="background: linear-gradient(135deg, #e8f0fe 0%, #c5d9ff 100%);">
                    <div class="catalog-thumb-icon" style="color: #1a73e8;">
                        <i class="fas fa-th-large"></i>
                    </div>
                </div>
                <div class="catalog-card-body">
                    <div class="catalog-card-meta">
                        <span class="catalog-domain">/dashboard</span>
                        <span class="catalog-tag">Operational</span>
                    </div>
                    <h3 class="catalog-card-title">Dashboard</h3>
                    <p class="catalog-card-desc">Kelola master data masjid, unit AC, dan operasional utama modul AC Service.</p>
                </div>
                <div class="catalog-card-footer">
                    <span class="catalog-visit">Buka <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>

            <a href="{{ auth()->check() ? route('monitoring') : route('login') }}" class="catalog-card">
                <div class="catalog-card-thumb" style="background: linear-gradient(135deg, #e6f4ea 0%, #a8d5b5 100%);">
                    <div class="catalog-thumb-icon" style="color: #1e8e3e;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="catalog-card-body">
                    <div class="catalog-card-meta">
                        <span class="catalog-domain">/monitoring</span>
                        <span class="catalog-tag">Workflow</span>
                    </div>
                    <h3 class="catalog-card-title">Monitoring</h3>
                    <p class="catalog-card-desc">Pantau service order, approval manager, dan progres pelayanan AC secara real-time.</p>
                </div>
                <div class="catalog-card-footer">
                    <span class="catalog-visit">Buka <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>
        </div>
    </div>
</section>
@endsection
