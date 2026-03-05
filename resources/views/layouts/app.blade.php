<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
<<<<<<< HEAD
    <meta name="theme-color" content="#4f46e5">
=======
    <meta name="theme-color" content="#1a73e8">
>>>>>>> e4258cdc0d298041d4477996327ae2a51c05c6f5
    <meta name="description" content="Sistem Manajemen Servis AC untuk Masjid dan Musholla">
    <title>@yield('title', 'AC Servis Masjid')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style-responsive-improvements.css') }}">
    <link rel="stylesheet" href="{{ asset('css/visual-enhancements.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<<<<<<< HEAD
</head>
<body>
    @include('layouts.header')

=======
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap">
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
>>>>>>> e4258cdc0d298041d4477996327ae2a51c05c6f5
    <main id="main-content" class="main-content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
<<<<<<< HEAD

        @yield('content')
    </main>

    @include('layouts.footer')
=======
        @yield('content')
    </main>
    @include('layouts.footer')
@endauth
>>>>>>> e4258cdc0d298041d4477996327ae2a51c05c6f5

    <!-- Global Popup Overlay -->
    <div class="overlay" id="overlay" onclick="closeAllPopups()"></div>

    <!-- Logout Confirm Popup -->
    <div class="popup" id="logoutPopup">
        <div class="popup-header">
            <h3><i class="fas fa-sign-out-alt"></i> Konfirmasi Logout</h3>
            <button class="popup-close" onclick="closePopup('logoutPopup')">&times;</button>
        </div>
        <div class="popup-body">
            <p>Apakah Anda yakin ingin logout?</p>
            <div class="popup-actions">
                <form action="{{ route('logout') }}" method="POST" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-danger">Ya, Logout</button>
                </form>
                <button class="btn btn-secondary" onclick="closePopup('logoutPopup')">Tidak</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
