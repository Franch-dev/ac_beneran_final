@extends('layouts.app')

@section('title', 'Monitoring AC — Anggota')

@section('content')
<section class="section">
    <div class="container">
        <div class="section-header" style="text-align:left;">
            <div class="features-eyebrow">Anggota</div>
            <h1>Monitoring untuk anggota</h1>
            <p>Status unit AC terkini (sampel 100 entri), sama dengan basis operasional — disajikan dalam konteks akses anggota (baca utama).</p>
        </div>

        <div class="table-container">
            <table class="data-table" aria-label="Unit AC anggota">
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
            <a href="{{ route('modules.ac-anggota.dashboard') }}" class="btn btn-outline btn-sm">← Dashboard anggota</a>
        </p>
    </div>
</section>
@endsection
