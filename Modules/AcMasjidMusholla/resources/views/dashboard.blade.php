@extends('layouts.app')

@section('title', 'Dashboard AC Masjid & Musholla')

@section('content')
<section class="section">
    <div class="container">
        <div class="section-header" style="text-align:left;">
            <div class="features-eyebrow">Kontrol panel</div>
            <h1>Dashboard Masjid &amp; Musholla</h1>
            <p>Gambaran cepat aset AC publik. Kelola detail masjid dan SPK di <a href="{{ route('dashboard') }}">modul operasional utama</a>.</p>
        </div>

        <div class="stat-grid" style="max-width: 560px; margin-bottom: 28px;">
            <div class="stat-item glass-card">
                <i class="fas fa-mosque text-primary"></i>
                <span class="stat-num">{{ $totalMasjid }}</span>
                <span class="stat-label">Masjid / musholla</span>
            </div>
            <div class="stat-item glass-card">
                <i class="fas fa-snowflake text-success"></i>
                <span class="stat-num">{{ $totalUnit }}</span>
                <span class="stat-label">Unit AC</span>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table" aria-label="Sampel lokasi">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Jenis</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sampleMasjid as $m)
                        <tr>
                            <td class="order-num">{{ $m->custom_id }}</td>
                            <td>{{ $m->name }}</td>
                            <td>{{ $m->type }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">Belum ada data masjid di basis layanan AC.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p style="margin-top: 20px; color: var(--text-muted); font-size: 0.9rem;">
            <a href="{{ route('modules.ac-masjid-musholla.monitoring') }}">Buka monitoring unit →</a>
            &nbsp;·&nbsp;
            <a href="{{ route('modules.ac-masjid-musholla.index') }}">Halaman layanan publik</a>
        </p>
    </div>
</section>
@endsection
