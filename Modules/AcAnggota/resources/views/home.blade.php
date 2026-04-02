@extends('layouts.app')

@section('title', 'AC Anggota — Forkis')

@section('content')
<section class="section">
    <div class="container" style="max-width: 920px;">
        <div class="section-header" style="text-align:left; margin-bottom: 28px;">
            <div class="features-eyebrow">Portal anggota</div>
            <h1 style="font-size: clamp(1.75rem, 4vw, 2.75rem); margin-bottom: 12px;">AC Anggota</h1>
            <p>Sudut layanan untuk anggota Forkis: ringkasan kapasitas servis, akses dashboard personal, dan status unit yang terhubung dengan data operasional bersama.</p>
        </div>

        <div class="info-banner" style="margin-bottom: 24px;">
            <i class="fas fa-users"></i>
            Indikator agregat: <strong>{{ $totalMasjid }}</strong> lokasi dalam jaringan,
            <strong>{{ $totalUnit }}</strong> unit AC (katalog servis bersama).
        </div>

        <div class="hero-actions" style="flex-wrap: wrap; gap: 12px;">
            <a href="{{ route('login', ['redirect' => '/modules/ac-anggota/dashboard']) }}" class="btn btn-primary btn-lg">
                <i class="fas fa-th-large"></i> Dashboard anggota
            </a>
            <a href="{{ route('login', ['redirect' => '/modules/ac-anggota/monitoring']) }}" class="btn btn-outline btn-lg">
                <i class="fas fa-chart-line"></i> Monitoring
            </a>
            <a href="{{ route('home') }}#katalog" class="btn btn-outline btn-lg">
                <i class="fas fa-arrow-left"></i> Katalog Platform
            </a>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container" style="max-width: 920px;">
        <div class="section-header" style="text-align:left;">
            <h2>Informasi akses</h2>
            <p>Gunakan akun Forkis yang sama (shared login). Setelah masuk, Anda dapat membuka dashboard dan monitoring modul ini. Untuk kebutuhan administrasi penuh, gunakan <a href="{{ route('modules.ac-service.index') }}">modul operasional AC Service</a>.</p>
        </div>
    </div>
</section>
@endsection
