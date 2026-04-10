@extends('layouts.app')

@section('title', 'Dashboard AC Anggota')

@section('content')
<section class="section">
    <div class="container">
        <div class="section-header" style="text-align:left;">
            <div class="features-eyebrow">Anggota</div>
            <h1>Dashboard AC Anggota</h1>
            <p>Fokus pada lokasi dengan kepadatan unit AC tertinggi — cocok untuk prioritas jadwal servis dan koordinasi lapangan.</p>
        </div>

        <div class="stat-grid" style="max-width: 560px; margin-bottom: 28px;">
            <div class="stat-item glass-card">
                <i class="fas fa-mosque text-primary"></i>
                <span class="stat-num">{{ $totalMasjid }}</span>
                <span class="stat-label">Lokasi jaringan</span>
            </div>
            <div class="stat-item glass-card">
                <i class="fas fa-snowflake text-success"></i>
                <span class="stat-num">{{ $totalUnit }}</span>
                <span class="stat-label">Total unit AC</span>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table" aria-label="Lokasi dengan unit terbanyak">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Jenis</th>
                        <th>Unit AC</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($anggotaHighlights as $m)
                        <tr>
                            <td class="order-num">{{ $m->custom_id }}</td>
                            <td>{{ $m->name }}</td>
                            <td>{{ $m->type }}</td>
                            <td>{{ $m->ac_units_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">Belum ada data untuk ditampilkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p style="margin-top: 20px; color: var(--text-muted); font-size: 0.9rem;">
            <a href="{{ route('modules.ac-anggota.monitoring') }}">Monitoring unit ?</a>
            &nbsp;·&nbsp;
            <a href="{{ route('modules.ac-anggota.index') }}">Halaman publik modul</a>
        </p>
    </div>
</section>
@endsection
