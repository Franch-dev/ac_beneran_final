<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <script>
      try {
        document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
      } catch(e) {}
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $viteCsp = app()->environment('local')
            ? 'http://127.0.0.1:5173 http://localhost:5173'
            : '';
    @endphp
    <meta http-equiv="Content-Security-Policy"
          content="default-src 'self'; frame-src 'self' https://www.google.com; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://unpkg.com {{ $viteCsp }}; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.gstatic.com https://cdnjs.cloudflare.com https://unpkg.com {{ $viteCsp }}; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com data:; img-src 'self' data: https;">
    <meta name="theme-color" content="#2F5D50">
    <meta name="description" content="Sistem Manajemen Servis AC untuk Masjid dan Musholla">
    <title>@yield('title', 'AC Servis Masjid')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style-responsive-improvements.css') }}">
    <link rel="stylesheet" href="{{ asset('css/visual-enhancements.css') }}">
    <link rel="stylesheet" href="{{ asset('css/operations-ui-overhaul.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ui-overhaul.css') }}">
    <!-- Liquid Glass Design System 2026 -->
    <link rel="stylesheet" href="{{ asset('css/liquid-glass.css') }}">
    <link rel="stylesheet" href="{{ asset('css/liquid-glass-components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/liquid-glass-integration.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        :root {
            scroll-padding-top: 5rem;
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.2s !important;
            }
            html { scroll-behavior: auto; }
        }
    </style>
</head>
@auth
<body class="has-sidebar">
    @include('layouts.header')
    <div class="sidebar-layout">
        <main id="main-content" class="sidebar-main">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
    @include('layouts.footer')
@else
<body>
    @include('layouts.header')
    <main id="main-content" class="main-content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
    @include('layouts.footer')
@endauth

    <!-- Global Popup Overlay -->
    <div class="overlay" id="overlay" onclick="closeAllPopups()"></div>

    <!-- Close Order Popup -->
    <div class="popup" id="closeOrderPopup">
        <div class="popup-header">
            <h3><i class="fas fa-check-circle"></i> Order Selesai</h3>
            <button class="popup-close" onclick="closePopup('closeOrderPopup')">&times;</button>
        </div>
        <div class="popup-body">
            <p>Pilih order yang ingin ditutup:</p>
            <form id="closeOrderForm" action="{{ route('service-orders.close') }}" method="POST">
                @csrf
                <div id="orderList"></div>
                <div class="popup-actions">
                    <button type="submit" class="btn btn-success">Selesai</button>
                    <button type="button" class="btn btn-secondary" onclick="closePopup('closeOrderPopup')">Batal</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Logout Confirm Popup -->
    <div class="popup" id="logoutPopup">
        <div class="popup-header">
            <h3><i class="fas fa-sign-out-alt"></i> Konfirmasi Logout</h3>
            <button class="popup-close" onclick="closePopup('logoutPopup')">&times;</button>
        </div>
        <div class="popup-body">
            <p>Apakah Anda yakin ingin logout?</p>
            <div class="popup-actions">
                <form action="{{ route('logout', [], false) }}" method="POST" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-danger">Ya, Logout</button>
                </form>
                <button class="btn btn-secondary" onclick="closePopup('logoutPopup')">Tidak</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/liquid-glass.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
