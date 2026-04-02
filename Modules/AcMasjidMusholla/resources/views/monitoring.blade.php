@extends('layouts.app')

@section('title', 'Monitoring AC — Masjid & Musholla')

@section('content')
<section class="section">
    <div class="container">
        <div class="section-header" style="text-align:left;">
            <div class="features-eyebrow">Status peralatan</div>
            <h1>Monitoring unit AC</h1>
            <p>Daftar unit terbaru di basis operasional (maks. 100 baris). Untuk mengubah data, gunakan modul utama.</p>
        </div>

        <div class="table-container">
            <table class="data-table" aria-label="Unit AC">
                <thead>
                    <tr>
                        <th>Lokasi</th>
                        <th>PK</th>
                        <th>Merek</th>
                        <th>Qty</th>
                        <th>Servis terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($units as $unit)
                        <tr>
                            <td>
                                @if ($unit->masjid)
                                    <strong>{{ $unit->masjid->name }}</strong>
                                    <div class="msi-id">{{ $unit->masjid->custom_id }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $unit->pk_type }}</td>
                            <td>{{ $unit->brand }}</td>
                            <td>{{ $unit->quantity }}</td>
                            <td>{{ $unit->last_service_date?->format('d M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">Tidak ada unit AC atau basis data belum tersedia.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p style="margin-top: 20px;">
            <a href="{{ route('modules.ac-masjid-musholla.dashboard') }}" class="btn btn-outline btn-sm">← Dashboard modul</a>
        </p>
    </div>
</section>
@endsection
