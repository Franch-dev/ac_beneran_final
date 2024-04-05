@extends('layouts.app')

@section('title', 'Future Module Workspace')

@section('content')
<section class="section">
    <div class="container" style="max-width: 1040px;">
        <div class="section-header" style="text-align:left; margin-bottom: 28px;">
            <div class="features-eyebrow">Future Module Workspace</div>
            <h1 style="font-size: clamp(1.9rem, 4vw, 3rem); margin-bottom: 12px;">Module Launch Checklist</h1>
            <p>Workspace terstruktur untuk memastikan modul baru lahir dengan alur login, navigasi, dan integrasi katalog yang sudah benar sejak hari pertama.</p>
        </div>

        <div class="pricing-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 28px;">
            <div class="pricing-card glass-card">
                <div class="pricing-header">
                    <span class="pricing-pk">Tracks</span>
                    <h3>{{ $summary['totalTracks'] }}</h3>
                </div>
                <p class="text-muted">Area kesiapan yang dipantau untuk modul baru.</p>
            </div>
            <div class="pricing-card glass-card">
                <div class="pricing-header">
                    <span class="pricing-pk">Ready</span>
                    <h3>{{ $summary['readyTracks'] }}</h3>
                </div>
                <p class="text-muted">Bagian yang sudah memenuhi kontrak platform.</p>
            </div>
            <div class="pricing-card glass-card">
                <div class="pricing-header">
                    <span class="pricing-pk">Queued</span>
                    <h3>{{ $summary['queuedTracks'] }}</h3>
                </div>
                <p class="text-muted">Bagian yang tinggal menunggu keputusan fitur berikutnya.</p>
            </div>
        </div>

        <div class="catalog-grid">
            @foreach ($tracks as $track)
                <div class="catalog-card glass-card">
                    <div class="catalog-card-body">
                        <div class="catalog-card-meta">
                            <span class="catalog-domain">{{ $track['owner'] }}</span>
                            <span class="catalog-tag">{{ ucfirst($track['status']) }}</span>
                        </div>
                        <h3 class="catalog-card-title">{{ $track['name'] }}</h3>
                        <p class="catalog-card-desc">{{ $track['notes'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
