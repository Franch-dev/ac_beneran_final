@auth
<!-- SIDEBAR (only for authenticated pages) -->
<aside class="sidebar glass" id="sidebar" aria-label="Navigasi sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="brand-icon"><i class="fas fa-snowflake"></i></div>
            <span class="sidebar-brand-text">AC Servis</span>
        </div>
        <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse sidebar" aria-label="Toggle sidebar">
            <i class="fas fa-chevron-left" id="collapseIcon"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav" role="navigation">
        <a href="{{ route('home') }}" class="sidebar-link {{ request()->routeIs('home') ? 'active' : '' }}" role="menuitem">
            <span class="sidebar-icon"><i class="fas fa-home"></i></span>
            <span class="sidebar-label">Home</span>
        </a>
        <a href="{{ route('home') }}#katalog" class="sidebar-link" role="menuitem">
            <span class="sidebar-icon"><i class="fas fa-layer-group"></i></span>
            <span class="sidebar-label">Katalog</span>
        </a>
        @php
            $currentModule = session('current_module');
            $dashboardRoute = $currentModule === 'ac-masjid-musholla' ? 'modules.ac-masjid-musholla.dashboard'
                        : ($currentModule === 'ac-anggota' ? 'modules.ac-anggota.dashboard'
                        : 'dashboard');
            $monitoringRoute = $currentModule === 'ac-masjid-musholla' ? 'modules.ac-masjid-musholla.monitoring'
                        : ($currentModule === 'ac-anggota' ? 'modules.ac-anggota.monitoring'
                        : 'monitoring');
            $moduleLabel = $currentModule === 'ac-masjid-musholla' ? 'AC Masjid'
                        : ($currentModule === 'ac-anggota' ? 'AC Anggota' : 'Dashboard');
            $moduleLabelMon = $currentModule === 'ac-masjid-musholla' ? 'AC Masjid'
                        : ($currentModule === 'ac-anggota' ? 'AC Anggota' : 'Monitoring');
        @endphp
        @if(in_array($currentModule, ['ac-masjid-musholla', 'ac-anggota']))
            <a href="{{ route($dashboardRoute) }}" class="sidebar-link {{ request()->routeIs('*dashboard*') ? 'active' : '' }}" role="menuitem">
                <span class="sidebar-icon"><i class="fas fa-th-large"></i></span>
                <span class="sidebar-label">{{ $moduleLabel }}</span>
            </a>
            <a href="{{ route($monitoringRoute) }}" class="sidebar-link monitoring-link {{ request()->routeIs('*monitoring*') ? 'active' : '' }}" role="menuitem">
                <span class="sidebar-icon"><i class="fas fa-chart-line"></i></span>
                <span class="sidebar-label">{{ $moduleLabelMon }}</span>
                <span class="sidebar-notification-stack" aria-label="Ringkasan antrean monitoring">
                    <span class="badge-lite badge-lite-pending" data-status-badge="pending" data-badge-label="Pending" hidden>0</span>
                    <span class="badge-lite badge-lite-invoice" data-status-badge="waiting_invoice" data-badge-label="Waiting Invoice" hidden>0</span>
                    <span class="badge-lite badge-lite-review" data-status-badge="waiting_review" data-badge-label="Waiting Review" hidden>0</span>
                </span>
            </a>
        @endif

        {{-- Profile (all roles) --}}
        <a href="{{ route('profile.index') }}"
           class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
           role="menuitem" data-tooltip="Profil Saya">
             <span class="sidebar-icon"><i class="fas fa-user-circle"></i></span>
             <span class="sidebar-label">Profil Saya</span>
        </a>

        {{-- Sitemap (all auth roles) - Disabled for debugging
        <a href="{{ route('sitemap') }}"
           class="sidebar-link {{ request()->routeIs('sitemap*') ? 'active' : '' }}"
           role="menuitem" data-tooltip="Sitemap">
             <span class="sidebar-icon"><i class="fas fa-sitemap"></i></span>
             <span class="sidebar-label">Sitemap</span>
        </a>
        --}}
@if(session('show_role_buttons', false))

        {{-- Remove sidebar button, moved to table actions --}}


        {{-- Admin only --}}
        @if(session('show_role_buttons', false) && auth()->user()->isAdmin())



        <a href="{{ route('users.index') }}"
           class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
           role="menuitem" data-tooltip="Manajemen User">
            <span class="sidebar-icon"><i class="fas fa-users-cog"></i></span>
            <span class="sidebar-label">Manajemen User</span>
        </a>
        <a href="{{ route('admin.logs.index') }}"
           class="sidebar-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}"
           role="menuitem" data-tooltip="Dashboard Log">
            <span class="sidebar-icon"><i class="fas fa-clipboard-list"></i></span>
            <span class="sidebar-label">Dashboard Log</span>
        </a>
        @endif

        {{-- Manager + Admin --}}
        @if(auth()->user()->isManager() || auth()->user()->isAdmin())
        <a href="{{ route('reports.index') }}"
           class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
           role="menuitem" data-tooltip="Laporan">
            <span class="sidebar-icon"><i class="fas fa-chart-bar"></i></span>
            <span class="sidebar-label">Laporan</span>
        </a>
        @endif
@endif

        {{-- Technician --}}
        @if(auth()->user()->isTechnician())
        <a href="{{ route('technician.dashboard') }}"
           class="sidebar-link {{ request()->routeIs('technician.*') ? 'active' : '' }}"
           role="menuitem" data-tooltip="Dashboard Teknisi">
            <span class="sidebar-icon"><i class="fas fa-tools"></i></span>
            <span class="sidebar-label">Dashboard Teknisi</span>
        </a>
        @endif

        {{-- Viewer --}}
        @if(auth()->user()->isViewer())
        <a href="{{ route('viewer.dashboard') }}"
           class="sidebar-link {{ request()->routeIs('viewer.*') ? 'active' : '' }}"
           role="menuitem" data-tooltip="Dashboard Viewer">
            <span class="sidebar-icon"><i class="fas fa-eye"></i></span>
            <span class="sidebar-label">Dashboard Viewer</span>
        </a>
        @endif
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-divider"></div>

        <div class="sidebar-user">
            <div class="sidebar-avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
                <span class="role-badge role-{{ auth()->user()->role }}">
                    {{ ucfirst(auth()->user()->role) }}
                </span>
            </div>
        </div>

        <div class="sidebar-actions">
            <button class="sidebar-action-btn"
                    onclick="toggleDarkMode()"
                    title="Toggle Dark Mode"
                    aria-label="Ganti mode gelap/terang">
                <i class="fas fa-moon" id="darkModeIcon"></i>
                <span class="sidebar-label" id="darkModeText">Mode Gelap</span>
            </button>

            <button class="sidebar-action-btn danger"
                    onclick="openPopup('logoutPopup')"
                    title="Logout"
                    aria-label="Logout">
                <i class="fas fa-sign-out-alt"></i>
                <span class="sidebar-label">Logout</span>
            </button>
        </div>
    </div>
</aside>

<!-- Mobile Top Bar (for sidebar pages) -->
<header class="mobile-topbar" id="mobileTopbar">
    <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Buka menu">
        <span></span><span></span><span></span>
    </button>
    <div class="navbar-brand">
        <div class="brand-icon"><i class="fas fa-snowflake"></i></div>
        <span>AC Servis Masjid</span>
    </div>
    <button class="btn-icon"
        id="headerDarkModeBtn"
        onclick="toggleDarkMode()"
        title="Toggle Dark Mode">
    <i class="fas fa-moon" id="darkModeIconMobile"></i>
    </button>
</header>

<!-- Mobile Overlay -->
<div class="sidebar-mobile-overlay" id="sidebarOverlay"></div>

@else
<!-- TOP NAVBAR (for guests/landing page) -->
<nav class="navbar glass-navbar" role="navigation" aria-label="Navigasi utama">
    <!-- Skip link removed per feedback -->

    <!-- Brand selalu tampil di header -->
    <div class="navbar-brand">
        <div class="brand-icon"><i class="fas fa-layer-group"></i></div>
        <span class="brand-text">Forkis Platform</span>
    </div>

    <!-- Toggle Mobile -->
    <button class="navbar-toggle"
            aria-expanded="false"
            aria-controls="navbar-menu"
            aria-label="Buka menu navigasi">
        <span class="hamburger-icon">
            <span></span>
            <span></span>
            <span></span>
        </span>
    </button>

    <!-- MENU / SIDEBAR MOBILE -->
    <div class="navbar-menu" id="navbar-menu">

        <!-- Brand di dalam mobile sidebar -->
        <div class="mobile-sidebar-header">
            <div class="brand-icon"><i class="fas fa-layer-group"></i></div>
            <span class="brand-text">Forkis Platform</span>
        </div>

        <div class="nav-sep"></div>

        <a href="#home"
           class="nav-link">
            <i class="fas fa-home"></i> Home
        </a>

        <a href="#katalog" class="nav-link ">
            <i class="fas fa-tags"></i> Katalog Website
        </a>

        <a href="#harga" class="nav-link">
            <i class="fas fa-tag"></i> Infaq Servis
        </a>

        <a href="#kontak" class="nav-link">
            <i class="fas fa-phone"></i> Kontak
        </a>

        <div class="nav-sep"></div>

        <button class="btn-icon" onclick="toggleDarkMode()">
            <i class="fas fa-moon" id="darkModeIconGuest"></i>
            <span id="darkModeTextGuest">Mode Gelap</span>
        </button>

        <a href="{{ \App\Support\PlatformNavigation::loginUrl() }}" class="btn btn-primary btn-sm">
            <i class="fas fa-sign-in-alt"></i> Login
        </a>
    </div>
</nav>

<div class="mobile-menu-overlay"></div>
@endauth
