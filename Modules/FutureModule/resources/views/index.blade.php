@extends('layouts.app')

@section('title', 'Future Module')

@section('content')
<section class="section">
    <div class="container" style="max-width: 860px;">
        <div class="section-header" style="text-align:left; margin-bottom: 24px;">
            <div class="features-eyebrow">Scalable Slot</div>
            <h1 style="font-size: clamp(2rem, 5vw, 3rem); margin-bottom: 12px;">Future Module</h1>
            <p>Slot modul generik untuk website berikutnya. Gunakan struktur ini saat menambah mini-website baru agar konsistensi route, domain, dan katalog tetap terjaga.</p>
        </div>
        <div class="info-banner">
            <i class="fas fa-rocket"></i>
            Entry point ini disiapkan untuk ekspansi berikutnya tanpa perlu mengubah fondasi katalog utama.
        </div>
    </div>
</section>
@endsection
