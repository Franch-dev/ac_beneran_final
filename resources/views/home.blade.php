@extends('layouts.app')

@section('title', 'Beranda - AC Servis Masjid')

@section('content')
<!-- Hero Section -->
<section class="hero" id="home">
    <div class="hero-content">
        <div class="hero-badge">🕌 Layanan Profesional</div>
        <h1>Sistem Servis AC<br><span class="gradient-text">Masjid & Musholla</span></h1>
        <p>Platform manajemen servis AC terpadu untuk masjid dan musholla. Kelola unit, jadwalkan servis, dan pantau kondisi AC secara real-time.</p>
        <div class="hero-actions">
            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt"></i> Mulai Sekarang
            </a>
            <a href="#keunggulan" class="btn btn-outline btn-lg">
                <i class="fas fa-info-circle"></i> Pelajari Lebih
            </a>
        </div>
    </div>
    <div class="hero-visual">
        <div class="hero-card">
            <div class="stat-grid">
                <div class="stat-item">
                    <i class="fas fa-mosque text-primary"></i>
                    <span class="stat-num">{{ $totalMasjid ?? '-' }}</span>
                    <span class="stat-label">Masjid</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-snowflake text-info"></i>
                    <span class="stat-num">{{ $totalUnit ?? '-' }}</span>
                    <span class="stat-label">Unit AC</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-tools text-success"></i>
                    <span class="stat-num">{{ $totalServis ?? '-' }}</span>
                    <span class="stat-label">Servis</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-star text-warning"></i>
                    <span class="stat-num">{{ $manualRating ?? '4.7' }}</span>
                    <span class="stat-label">Rating</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Keunggulan -->
<section class="section features-section" id="keunggulan">
    <div class="section-header container">
        <div class="features-eyebrow">✦ Keunggulan Kami</div>
        <h2>Layanan Terbaik untuk<br><span class="gradient-text">Masjid & Musholla</span></h2>
        <p>Platform manajemen AC terpercaya yang sudah digunakan ratusan masjid</p>
    </div>

    <!-- Marquee Carousel -->
    <div class="features-marquee-wrapper">
        <div class="features-marquee" id="featuresMarquee">
            <div class="marquee-track" id="marqueeTrack">
                <!-- Card 1 -->
                <div class="fcard">
                    <div class="fcard-icon" style="background: #e8f0fe; color: #1a73e8">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="fcard-title">Cepat & Responsif</h3>
                    <p class="fcard-desc">Tim teknisi berpengalaman siap merespons dalam 24 jam untuk kebutuhan servis AC masjid Anda.</p>
                    <div class="fcard-tag"> 24 Jam Respons</div>
                </div>
                <!-- Card 2 -->
                <div class="fcard">
                    <div class="fcard-icon" style="background: #e6f4ea; color: #1e8e3e">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="fcard-title">Bergaransi</h3>
                    <p class="fcard-desc">Setiap pekerjaan servis dilengkapi dengan garansi layanan resmi selama 3 bulan penuh.</p>
                    <div class="fcard-tag"> Garansi 3 Bulan</div>
                </div>
                <!-- Card 3 -->
                <div class="fcard">
                    <div class="fcard-icon" style="background: #fef0cd; color: #b06000">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="fcard-title">Monitoring Real-time</h3>
                    <p class="fcard-desc">Pantau kondisi dan status servis AC masjid kapan saja dan di mana saja secara langsung.</p>
                    <div class="fcard-tag"> Live Dashboard</div>
                </div>
                <!-- Card 4 -->
                <div class="fcard">
                    <div class="fcard-icon" style="background: #fce8e6; color: #c5221f">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h3 class="fcard-title">Laporan Lengkap</h3>
                    <p class="fcard-desc">SPK dan invoice terstruktur untuk setiap pekerjaan servis yang dilakukan secara transparan.</p>
                    <div class="fcard-tag"> SPK & Invoice</div>
                </div>
                <!-- Card 5 -->
                <div class="fcard">
                    <div class="fcard-icon" style="background: #f3e8fd; color: #7b1fa2">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3 class="fcard-title">Notifikasi Otomatis</h3>
                    <p class="fcard-desc">Sistem otomatis mengingatkan jadwal servis berdasarkan urgensi dan kondisi AC.</p>
                    <div class="fcard-tag"> Smart Alert</div>
                </div>
                <!-- Card 6 -->
                <div class="fcard">
                    <div class="fcard-icon" style="background: #e0f7fa; color: #0097a7">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3 class="fcard-title">Multi-Role Access</h3>
                    <p class="fcard-desc">Sistem hak akses terstruktur untuk Frontdesk, Manager, dan Admin dalam satu platform.</p>
                    <div class="fcard-tag"> 3 Level Akses</div>
                </div>
                <!-- DUPLICATE for seamless loop -->
                <div class="fcard">
                    <div class="fcard-icon" style="background: #e8f0fe; color: #1a73e8">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="fcard-title">Cepat & Responsif</h3>
                    <p class="fcard-desc">Tim teknisi berpengalaman siap merespons dalam 24 jam untuk kebutuhan servis AC masjid Anda.</p>
                    <div class="fcard-tag"> 24 Jam Respons</div>
                </div>
                <div class="fcard">
                    <div class="fcard-icon" style="background: #e6f4ea; color: #1e8e3e">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="fcard-title">Bergaransi</h3>
                    <p class="fcard-desc">Setiap pekerjaan servis dilengkapi dengan garansi layanan resmi selama 3 bulan penuh.</p>
                    <div class="fcard-tag"> Garansi 3 Bulan</div>
                </div>
                <div class="fcard">
                    <div class="fcard-icon" style="background: #fef0cd; color: #b06000">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="fcard-title">Monitoring Real-time</h3>
                    <p class="fcard-desc">Pantau kondisi dan status servis AC masjid kapan saja dan di mana saja secara langsung.</p>
                    <div class="fcard-tag"> Live Dashboard</div>
                </div>
                <div class="fcard">
                    <div class="fcard-icon" style="background: #fce8e6; color: #c5221f">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h3 class="fcard-title">Laporan Lengkap</h3>
                    <p class="fcard-desc">SPK dan invoice terstruktur untuk setiap pekerjaan servis yang dilakukan secara transparan.</p>
                    <div class="fcard-tag"> SPK & Invoice</div>
                </div>
                <div class="fcard">
                    <div class="fcard-icon" style="background: #f3e8fd; color: #7b1fa2">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3 class="fcard-title">Notifikasi Otomatis</h3>
                    <p class="fcard-desc">Sistem otomatis mengingatkan jadwal servis berdasarkan urgensi dan kondisi AC.</p>
                    <div class="fcard-tag"> Smart Alert</div>
                </div>
                <div class="fcard">
                    <div class="fcard-icon" style="background: #e0f7fa; color: #0097a7">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3 class="fcard-title">Multi-Role Access</h3>
                    <p class="fcard-desc">Sistem hak akses terstruktur untuk Frontdesk, Manager, dan Admin dalam satu platform.</p>
                    <div class="fcard-tag"> 3 Level Akses</div>
                </div>
            </div>
        </div>
        <!-- Fade edges -->
        <div class="marquee-fade-left"></div>
        <div class="marquee-fade-right"></div>
    </div>

    <!-- Pause on hover hint -->
    <p class="marquee-hint container">Hover untuk berhenti · Scroll untuk melihat semua fitur</p>
</section>
</section>

<!-- Harga Servis -->
<section class="section section-alt" id="harga">
    <div class="container">
        <div class="section-header">
            <h2>Harga Servis</h2>
            <p>Harga transparan, kualitas terjamin</p>
        </div>
        <div class="pricing-grid">
            <div class="pricing-card">
                <div class="pricing-header">
                    <span class="pricing-pk">1 PK</span>
                    <h3>Standar</h3>
                </div>
                <div class="pricing-price">
                    <span class="price">Rp 150.000</span>
                    <span class="price-unit">/ unit</span>
                </div>
                <ul class="pricing-features">
                    <li><i class="fas fa-check"></i> Cuci Filter</li>
                    <li><i class="fas fa-check"></i> Cek Freon</li>
                    <li><i class="fas fa-check"></i> Pembersihan Evaporator</li>
                    <li><i class="fas fa-check"></i> Garansi 3 Bulan</li>
                </ul>
            </div>
            <div class="pricing-card pricing-featured">
                <div class="pricing-badge">Terpopuler</div>
                <div class="pricing-header">
                    <span class="pricing-pk">2 PK</span>
                    <h3>Premium</h3>
                </div>
                <div class="pricing-price">
                    <span class="price">Rp 200.000</span>
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
            <div class="pricing-card">
                <div class="pricing-header">
                    <span class="pricing-pk">5 PK</span>
                    <h3>Enterprise</h3>
                </div>
                <div class="pricing-price">
                    <span class="price">Rp 350.000</span>
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

<!-- Kontak -->
<section class="section" id="kontak">
    <div class="container">
        <div class="section-header">
            <h2>Hubungi Kami</h2>
            <p>Tim CS kami siap membantu Anda</p>
        </div>
        <div class="contact-grid">
            <div class="contact-card">
                <i class="fab fa-whatsapp"></i>
                <h3>WhatsApp CS</h3>
                <p>0895-3259-13693</p>
                <a href="https://wa.me/62895325913693" class="btn btn-success btn-sm" target="_blank">
                    <i class="fab fa-whatsapp"></i> Chat Sekarang
                </a>
            </div>
            <div class="contact-card">
                <i class="fas fa-envelope"></i>
                <h3>Email</h3>
                <p>sekretariat@forkis.org</p>
                <a href="mailto:sekretariat@forkis.org" class="btn btn-primary btn-sm">
                    <i class="fas fa-envelope"></i> Kirim Email
                </a>
            </div>
            <div class="contact-card">
                <i class="fas fa-phone"></i>
                <h3>Telepon</h3>
                <p>885 1031</p>
                <a href="tel:8851031" class="btn btn-info btn-sm">
                    <i class="fas fa-phone"></i> Hubungi
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Lokasi & Maps -->
<section class="location-section">
    <div class="container text-center">
        <h2>Lokasi Kami</h2>
        <div style="max-width:820px;margin:0 auto 1.5rem;">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d991.964964857857!2d106.9620843!3d-6.211166!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e698b80e955010d:0x83a105adcc080c6b!2sSekretariat%20Forkis!5e0!3m2!1sen!2sid!4v1700000000000!5m2!1sen!2sid" width="100%" height="320" style="border:0;border-radius:12px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <a href="https://www.google.com/maps/place/Sekretariat+Forkis/@-6.211166,106.9620843,786m/data=!3m1!1e3!4m6!3m5!1s0x2e698b80e955010d:0x83a105adcc080c6b!8m2!3d-6.2109742!4d106.9635937!16s%2Fg%2F11b5phqxxs?entry=ttu&g_ep=EgoyMDI2MDIyMy4wIKXMDSoASAFQAw%3D%3D" target="_blank" class="btn btn-success btn-sm mb-2">Buka di Google Maps</a>
        </div>
    </div>
</section>


<!-- CTA -->
<section class="cta-section">
    <div class="container text-center">
        <h2>Siap Mengelola Servis AC Masjid?</h2>
        <p>Bergabung dengan ratusan masjid yang sudah mempercayakan manajemen AC mereka kepada kami.</p>
        <a href="{{ route('login') }}" class="btn btn-white btn-lg">
            <i class="fas fa-rocket"></i> Login Sekarang
        </a>
    </div>
</section>
@endsection
