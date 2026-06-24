<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <script>
      try {
        document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
      } catch(e) {}

      window.syncPopupState = window.syncPopupState || function () {
        var overlay = document.getElementById('overlay');
        var hasOpenPopup = document.querySelectorAll('.popup.active').length > 0;

        if (overlay) {
          overlay.classList.toggle('active', hasOpenPopup);
        }

        if (document.body) {
          document.body.classList.toggle('popup-open', hasOpenPopup);
          document.body.style.overflow = hasOpenPopup ? 'hidden' : '';
        }
      };

      window.openPopup = window.openPopup || function (id) {
        var popup = document.getElementById(id);
        if (!popup) {
          return;
        }

        popup.classList.add('active');
        window.syncPopupState();
      };

      window.closePopup = window.closePopup || function (id) {
        var popup = document.getElementById(id);
        if (popup) {
          popup.classList.remove('active');
        }

        window.syncPopupState();
      };

      window.closeAllPopups = window.closeAllPopups || function () {
        document.querySelectorAll('.popup.active').forEach(function (popup) {
          popup.classList.remove('active');
        });

        window.syncPopupState();
      };

      window.showToast = window.showToast || function (message, type) {
        var toast = document.createElement('div');
        toast.className = 'toast-notification toast-' + (type || 'info');
        toast.textContent = message;
        toast.style.cssText = 'position:fixed;right:16px;bottom:16px;z-index:10000;padding:12px 16px;border-radius:8px;background:#111827;color:#fff;box-shadow:0 12px 30px rgba(15,23,42,.18);max-width:min(360px,calc(100vw - 32px));font:500 14px/1.4 system-ui,sans-serif;';
        document.body.appendChild(toast);
        setTimeout(function () { toast.remove(); }, 3500);
      };

      window.toggleDarkMode = window.toggleDarkMode || function () {
        var root = document.documentElement;
        var nextTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';

        root.setAttribute('data-theme', nextTheme);

        try {
          localStorage.setItem('theme', nextTheme);
        } catch(e) {}

        document.querySelectorAll('#darkModeIcon, #darkModeIconMobile, #darkModeIconGuest').forEach(function (icon) {
          icon.className = nextTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });

        ['darkModeText', 'darkModeTextGuest'].forEach(function (id) {
          var element = document.getElementById(id);
          if (element) {
            element.textContent = nextTheme === 'dark' ? 'Mode Terang' : 'Mode Gelap';
          }
        });
      };
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
            <form id="closeOrderForm" action="{{ route('service-orders.close') }}" method="POST"
                data-confirm="Order terpilih akan ditandai selesai."
                data-confirm-heading="Selesaikan order?"
                data-confirm-type="success"
                data-confirm-text="Ya, Selesaikan">
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
                <form action="{{ route('logout', [], false) }}" method="POST" style="display:inline"
                    data-confirm="Anda akan keluar dari sesi saat ini."
                    data-confirm-heading="Logout?"
                    data-confirm-type="warning"
                    data-confirm-text="Ya, Logout">
                    @csrf
                    <button type="submit" class="btn btn-danger">Ya, Logout</button>
                </form>
                <button class="btn btn-secondary" onclick="closePopup('logoutPopup')">Tidak</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/liquid-glass.js') }}" defer></script>
    <script>
      (function () {
        function formatCounterValue(value, decimals) {
          return decimals ? value.toFixed(1) : String(Math.round(value));
        }

        function animateCounter(counter) {
          if (!counter || counter.dataset.counterInitialized === 'true') {
            return;
          }

          counter.dataset.counterInitialized = 'true';

          var rawTarget = counter.getAttribute('data-target') || '0';
          var target = parseFloat(rawTarget) || 0;
          var decimals = rawTarget.indexOf('.') !== -1 ? 1 : 0;
          var startTime = performance.now();
          var duration = 900;

          function update(time) {
            var elapsed = Math.min((time - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - elapsed, 3);
            counter.textContent = formatCounterValue(target * eased, decimals);

            if (elapsed < 1) {
              requestAnimationFrame(update);
            }
          }

          if (typeof requestAnimationFrame !== 'function') {
            counter.textContent = formatCounterValue(target, decimals);
            return;
          }

          requestAnimationFrame(update);
        }

        window.initFallbackCounters = window.initFallbackCounters || function (root) {
          var scope = root || document;
          var counters = scope.querySelectorAll ? scope.querySelectorAll('.counter') : [];

          counters.forEach(function (counter) {
            if ('IntersectionObserver' in window) {
              var observer = new IntersectionObserver(function (entries, currentObserver) {
                entries.forEach(function (entry) {
                  if (!entry.isIntersecting) {
                    return;
                  }

                  animateCounter(entry.target);
                  currentObserver.unobserve(entry.target);
                });
              }, { threshold: 0.2 });

              observer.observe(counter);
              return;
            }

            animateCounter(counter);
          });
        };

        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', function () {
            window.initFallbackCounters();
          }, { once: true });
        } else {
          window.initFallbackCounters();
        }

        document.addEventListener('ac-sync:rendered', function () {
          window.initFallbackCounters();
        });
      })();
    </script>
    @stack('scripts')
</body>
</html>
