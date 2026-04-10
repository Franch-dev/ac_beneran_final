@extends('layouts.app')

@section('title', 'Dashboard AC Anggota')

@section('content')
<section class="section">
    <div class="container">
        <div class="section-header" style="text-align:left;">
            <div class="features-eyebrow">Kontrol panel</div>
            <h1>Dashboard AC Anggota</h1>
            <p>Gambaran cepat aset AC anggota. Kelola detail anggota dan SPK di <a href="{{ route('dashboard') }}">modul operasional utama</a>.</p>
        </div>

        <div class="stat-grid" style="max-width: 560px; margin-bottom: 28px;">
            <div class="stat-item glass-card">
                <i class="fas fa-users text-primary"></i>
                <span class="stat-num">{{ $totalAnggota }}</span>
                <span class="stat-label">Anggota</span>
            </div>
            <div class="stat-item glass-card">
                <i class="fas fa-snowflake text-success"></i>
                <span class="stat-num">{{ $totalUnit }}</span>
                <span class="stat-label">Unit AC</span>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table" aria-label="Sampel anggota">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Jenis</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sampleAnggota as $anggota)
                        <tr>
                            <td class="order-num">{{ $anggota->custom_id }}</td>
                            <td>{{ $anggota->name }}</td>
                            <td>{{ $anggota->type }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">Belum ada data anggota di basis layanan AC.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p style="margin-top: 20px; color: var(--text-muted); font-size: 0.9rem;">
            <a href="{{ route('ac-anggota.monitoring') }}">Buka monitoring unit &rarr;</a>
            &nbsp;&middot;&nbsp;
            <a href="{{ route('ac-anggota.index') }}">Halaman layanan publik</a>
        </p>
    </div>
</section>
@endsection
