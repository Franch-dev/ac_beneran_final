@extends('layouts.app')

@section('title', 'Forkis Platform')

@section('content')
@php
    $catalogCountValue = $catalogCount ?? 0;
    $compactCatalogClass = in_array($catalogCountValue, [1, 2], true)
        ? 'catalog-grid--compact catalog-grid--compact-' . $catalogCountValue
        : '';
    $heroPrimaryUrl = auth()->check() ? '#katalog' : \App\Support\PlatformNavigation::loginUrl();
    $heroPrimaryLabel = auth()->check() ? 'Jelajahi Katalog' : 'Mulai Sekarang';
    $heroSecondaryUrl = '#katalog';
    $heroSecondaryLabel = 'Lihat Katalog';

    if (auth()->check()) {
        $heroSecondaryUrl = route('profile.index');
        $heroSecondaryLabel = 'Profil Saya';
        $user = auth()->user();

        if ($user->isTechnician()) {
            $heroSecondaryUrl = route('technician.dashboard');
            $heroSecondaryLabel = 'Dashboard Teknisi';
        } elseif ($user->isViewer()) {
            $heroSecondaryUrl = route('viewer.dashboard');
            $heroSecondaryLabel = 'Dashboard Viewer';
        } elseif ($user->isManager() || $user->isAdmin() || $user->isFrontdesk()) {
            $heroSecondaryUrl = route('dashboard');
            $heroSecondaryLabel = 'Dashboard Utama';
        }
    }
@endphp


    <section class="hero glass-hero" id="home" data-aos="fade-up">
        <div class="hero-content">
        <div class="hero-badge">Website Catalog</div>
        <h1>Platform Modular<br><span class="gradient-text">Online Forkis</span></h1>
        <p>Satu pintu untuk mengakses mini-website internal Forkis. Saat ini ada <strong class="hero-inline-total">{{ $catalogCountValue }} website</strong> yang siap dijelajahi dari katalog utama dengan fondasi shared login dan jalur ekspansi ke subdomain.</p>
        <div class="hero-actions">
            <a href="{{ $heroPrimaryUrl }}" class="btn btn-primary btn-lg">
                <i class="fas {{ auth()->check() ? 'fa-compass' : 'fa-sign-in-alt' }}"></i> {{ $heroPrimaryLabel }}
            </a>
            <a href="{{ $heroSecondaryUrl }}" class="btn btn-outline btn-lg">
                <i class="fas {{ auth()->check() ? 'fa-th-large' : 'fa-layer-group' }}"></i> {{ $heroSecondaryLabel }}
            </a>
        </div>
    </div>
    <div class="hero-visual">
        <div class="glass-card hero-card hero-service-card" data-aos="fade-up" data-aos-delay="120">
            <div class="hero-service-topbar">
                <div class="hero-service-kicker">
                    <i class="fas fa-wave-square"></i>
                    <span>Forkis Platform Pulse</span>
                </div>
                <span class="hero-service-pill">Live</span>
            </div>

            <div class="hero-service-spotlight">
                <div class="hero-service-orbit hero-service-orbit--outer" aria-hidden="true"></div>
                <div class="hero-service-orbit hero-service-orbit--inner" aria-hidden="true"></div>

                <div class="hero-service-core">
                    <span class="hero-service-label">Website Aktif</span>
                    <div class="hero-service-value-wrap">
                        <span class="hero-service-value counter" data-target="{{ $catalogCountValue }}">0</span>
                    </div>
                    <span class="hero-service-caption">modul internal yang siap dibuka dari katalog utama</span>
                </div>
            </div>

            <div class="hero-service-support-grid">
                <div class="hero-support-card hero-support-card--mosque">
                    <div class="hero-support-icon">
                        <i class="fas fa-mosque"></i>
                    </div>
                    <span class="hero-support-value counter" data-target="{{ $totalMasjid ?? 0 }}">0</span>
                    <span class="hero-support-label">Masjid Aktif</span>
                </div>

                <div class="hero-support-card hero-support-card--unit">
                    <div class="hero-support-icon">
                        <i class="fas fa-snowflake"></i>
                    </div>
                    <span class="hero-support-value counter" data-target="{{ $totalUnit ?? 0 }}">0</span>
                    <span class="hero-support-label">Unit AC</span>
                </div>

                <div class="hero-support-card hero-support-card--rating">
                    <div class="hero-support-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <span class="hero-support-value counter" data-target="{{ $manualRating ?? 4.7 }}">0</span>
                    <span class="hero-support-label">Rating Layanan</span>
                </div>
            </div>

            <div class="hero-service-status glass-card">
                <span class="hero-service-status-dot"></span>
                <span>Arsitektur modular Forkis kini siap berkembang dari satu katalog menuju banyak website internal.</span>
            </div>
        </div>
    </div>
</section>

<section class="section catalog-section" id="katalog">
    <div class="container">
        <div class="section-header">
            <div class="features-eyebrow">Platform Catalog</div>
            <h2>Jelajahi Website<br><span class="gradient-text">Internal Forkis</span></h2>
            <p>Pilih modul yang Anda butuhkan dari satu katalog website internal yang disiapkan untuk tumbuh bersama platform.</p>
        </div>

<div class="catalog-grid {{ $compactCatalogClass }}" data-aos="fade-up" data-aos-delay="100">
            @forelse ($catalogModules as $module)
                @php $hasGuestOrder = ! empty($module['guest_order_url']); @endphp
                <div class="catalog-card glass-card" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                    <div class="catalog-card-thumb" style="background: {{ $module['thumb_background'] }};">
                        <div class="catalog-thumb-icon" style="color: {{ $module['thumb_color'] }};">
                            <i class="{{ $module['icon'] }}"></i>
                        </div>
                        @if (! empty($module['badge']))
                            <div class="catalog-card-badge {{ $module['badge_variant'] }}">{{ $module['badge'] }}</div>
                        @endif
                    </div>
                    <div class="catalog-card-body">
                        <div class="catalog-card-meta">
                            <span class="catalog-domain">{{ $module['domain_label'] }}</span>
                            <span class="catalog-tag">{{ $module['tag'] }}</span>
                        </div>
                        <h3 class="catalog-card-title">{{ $module['headline'] }}</h3>
                        <p class="catalog-card-desc">{{ $module['description'] }}</p>
                    </div>
                    <div class="catalog-card-footer">
                        <a href="{{ $module['url'] }}" class="catalog-card-link">
                            {{ $module['cta_label'] }} <i class="fas fa-arrow-right"></i>
                        </a>
                        @if ($hasGuestOrder)
                            <button
                                type="button"
                                class="btn btn-outline btn-sm guest-order-btn"
                                style="margin-top:0.75rem; display:inline-flex; align-items:center; gap:0.5rem;"
                                data-guest-order-action="{{ route($module['guest_order_store_route_name']) }}"
                                data-guest-order-label="{{ $module['headline'] }}"
                                onclick='window.openGuestOrderPopup?.(this.dataset.guestOrderAction, this.dataset.guestOrderLabel)'
                            >
                                <i class="fas fa-file-invoice"></i> Ajukan Service
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="catalog-card glass-card">
                    <div class="catalog-card-body">
                        <div class="catalog-card-meta">
                            <span class="catalog-domain">catalog</span>
                            <span class="catalog-tag">Empty</span>
                        </div>
                        <h3 class="catalog-card-title">Belum Ada Modul</h3>
                        <p class="catalog-card-desc">Tambahkan modul pertama di konfigurasi katalog untuk menampilkan website internal di landing page.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>

@include('partials.guest-order-popup', [
    'masjids' => $masjids,
    'formActionRoute' => route('modules.ac-service.guest-order.store'),
    'popupTitle' => 'Forkis Platform',
])

<section class="section section-alt" id="harga">
    <div class="container">
        <div class="section-header">
            <h2>Infaq Biaya Servis</h2>
            <p>Harga transparan, kualitas terjamin</p>
        </div>
        <div class="pricing-grid">
            <div class="pricing-card glass-card">
                <div class="pricing-header">
                    <span class="pricing-pk">1 PK</span>
                    <h3>Standar</h3>
                </div>
                <div class="pricing-price">
                    <span class="price">Rp 40.000</span>
                    <span class="price-unit">/ unit</span>
                </div>
                <ul class="pricing-features">
                    <li><i class="fas fa-check"></i> Cuci Filter</li>
                    <li><i class="fas fa-check"></i> Cek Freon</li>
                    <li><i class="fas fa-check"></i> Pembersihan Evaporator</li>
                    <li><i class="fas fa-check"></i> Garansi 3 Bulan</li>
                </ul>
            </div>
            <div class="pricing-card pricing-featured glass-card" data-aos="fade-up" data-aos-delay="100">
                <div class="pricing-badge">Terpopuler</div>
                <div class="pricing-header">
                    <span class="pricing-pk">2 PK</span>
                    <h3>Premium</h3>
                </div>
                <div class="pricing-price">
                    <span class="price">Rp 45.000</span>
                    <span class="price-unit">/ unit</span>
                </div>
                <ul class="pricing-features">
                    <li><i class="fas fa-check"></i> Cuci Filter</li>
                    <li><i class="fas fa-check"></i> Isi Freon</li>
                    <li><i class="fas fa-check"></i> Pembersihan Menyeluruh</li>
                    <li><i class="fas fa-check"></i> Cek Kompresor</li>
                    <li><i class="fas fa-check"></i> Garansi 3 Bulan</li>
                </ul>
            </div>
            <div class="pricing-card glass-card" data-aos="fade-up" data-aos-delay="100">
                <div class="pricing-header">
                    <span class="pricing-pk">5 PK</span>
                    <h3>Enterprise</h3>
                </div>
                <div class="pricing-price">
                    <span class="price">Rp 80.000</span>
                    <span class="price-unit">/ unit</span>
                </div>
                <ul class="pricing-features">
                    <li><i class="fas fa-check"></i> Servis Lengkap</li>
                    <li><i class="fas fa-check"></i> Isi Freon</li>
                    <li><i class="fas fa-check"></i> Pembersihan Total</li>
                    <li><i class="fas fa-check"></i> Pengecekan Sistem</li>
                    <li><i class="fas fa-check"></i> Garansi 3 Bulan</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section" id="kontak">
    <div class="container">
        <div class="section-header">
            <h2>Hubungi Kami</h2>
            <p>Tim CS kami siap membantu Anda</p>
        </div>
        <div class="contact-grid">
            <div class="contact-card glass-card" data-aos="fade-up" data-aos-delay="100">
                <i class="fab fa-whatsapp"></i>
                <h3>WhatsApp CS</h3>
                <p>0895-3259-13693</p>
                <a href="https://wa.me/62895325913693" class="btn btn-success btn-sm" target="_blank">
                    <i class="fab fa-whatsapp"></i> Chat Sekarang
                </a>
            </div>
            <div class="contact-card glass-card" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-envelope"></i>
                <h3>Email</h3>
                <p>sekretariat@forkis.org</p>
                <a href="mailto:sekretariat@forkis.org" class="btn btn-primary btn-sm">
                    <i class="fas fa-envelope"></i> Kirim Email
                </a>
            </div>
            <div class="contact-card glass-card" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-phone"></i>
                <h3>Telepon</h3>
                <p>885 1031</p>
                <a href="tel:8851031" class="btn btn-info btn-sm">
                    <i class="fas fa-phone"></i> Hubungi
                </a>
            </div>
        </div>

        <div class="map-container text-center" style="margin-top: 3rem; padding: 0 1rem;">
            <div style="max-width:820px;margin:0 auto 1.5rem; width:100%;">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d991.964964857857!2d106.9620843!3d-6.211166!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e698b80e955010d:0x83a105adcc080c6b!2sSekretariat%20Forkis!5e0!3m2!1sen!2sid!4v1700000000000!5m2!1sen!2sid" width="100%" height="320" style="border:0;border-radius:12px;box-shadow: 0 4px 16px rgba(0,0,0,0.06);margin-bottom:1rem;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                <a href="https://www.google.com/maps/place/Sekretariat+Forkis/@-6.211166,106.9620843,786m/data=!3m1!1e3!4m6!3m5!1s0x2e698b80e955010d:0x83a105adcc080c6b!8m2!3d-6.2109742!4d106.9635937!16s%2Fg%2F11b5phqxxs?entry=ttu&g_ep=EgoyMDI2MDIyMy4wIKXMDSoASAFQAw%3D%3D" target="_blank" class="btn btn-success btn-sm mb-2">
                    <i class="fas fa-map-marker-alt"></i> Buka di Google Maps
                </a>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container text-center">
        <h2>{{ auth()->check() ? 'Platform Forkis Siap Dipakai' : 'Siap Masuk ke Platform Forkis?' }}</h2>
        <p>{{ auth()->check() ? 'Masuk ke katalog utama lalu pilih hub kerja yang Anda butuhkan.' : 'Login sekali untuk mengakses katalog website internal dan modul operasional Forkis dari satu pintu.' }}</p>
        <a href="{{ auth()->check() ? '#katalog' : \App\Support\PlatformNavigation::loginUrl() }}" class="btn btn-white btn-lg">
            <i class="fas {{ auth()->check() ? 'fa-layer-group' : 'fa-rocket' }}"></i> {{ auth()->check() ? 'Buka Katalog' : 'Login Sekarang' }}
        </a>
    </div>
</section>


@endsection
