<<<<<<< HEAD
<nav class="navbar" role="navigation" aria-label="Navigasi utama">
    <a href="#main-content" class="skip-link">Langsung ke konten utama</a>

    <div class="navbar-brand">
        <div class="brand-icon" aria-hidden="true"><i class="fas fa-snowflake"></i></div>
        <span>AC Servis Masjid</span>
    </div>

    <div class="navbar-menu" id="navbar-menu" role="menubar">
        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" role="menuitem">
            <i class="fas fa-home" aria-hidden="true"></i> Home
        </a>

        @auth
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" role="menuitem">
                <i class="fas fa-th-large" aria-hidden="true"></i> Dashboard
            </a>
            <a href="{{ route('monitoring') }}" class="nav-link {{ request()->routeIs('monitoring') ? 'active' : '' }}" role="menuitem">
                <i class="fas fa-chart-line" aria-hidden="true"></i> Monitoring
            </a>
            <div class="nav-sep" role="separator"></div>
            <div class="nav-user" role="menuitem" aria-label="Pengguna: {{ auth()->user()->name }}, peran: {{ auth()->user()->role }}">
                <i class="fas fa-user-circle" aria-hidden="true"></i>
                <span>{{ auth()->user()->name }}</span>
                <span class="role-badge role-{{ auth()->user()->role }}">{{ ucfirst(auth()->user()->role) }}</span>
            </div>
            <button class="btn-icon" onclick="toggleDarkMode()" title="Toggle Dark Mode" aria-label="Ganti mode gelap/terang" role="menuitem">
                <i class="fas fa-moon" id="darkModeIcon" aria-hidden="true"></i>
                <span class="d-inline d-md-none ms-2">Mode</span>
            </button>
            <button class="btn-icon text-danger" onclick="openPopup('logoutPopup')" title="Logout" aria-label="Logout" role="menuitem">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                <span class="d-inline d-md-none ms-2">Logout</span>
            </button>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm" role="menuitem">
                <i class="fas fa-sign-in-alt" aria-hidden="true"></i> Login
            </a>
        @endauth
    </div>

=======
@auth
<!-- SIDEBAR (only for authenticated pages) -->
<aside class="sidebar" id="sidebar" aria-label="Navigasi sidebar">
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
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" role="menuitem">
            <span class="sidebar-icon"><i class="fas fa-th-large"></i></span>
            <span class="sidebar-label">Dashboard</span>
        </a>
        <a href="{{ route('monitoring') }}" class="sidebar-link {{ request()->routeIs('monitoring') ? 'active' : '' }}" role="menuitem">
            <span class="sidebar-icon"><i class="fas fa-chart-line"></i></span>
            <span class="sidebar-label">Monitoring</span>
        </a>
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

    <!-- ✅ Tambahkan wrapper ini -->
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
<nav class="navbar" role="navigation" aria-label="Navigasi utama">
    <a href="#main-content" class="skip-link">Langsung ke konten utama</a>

    <!-- Brand selalu tampil di header -->
    <div class="navbar-brand">
        <div class="brand-icon"><i class="fas fa-snowflake"></i></div>
        <span class="brand-text">AC Servis Masjid</span>
    </div>

    <!-- Toggle Mobile -->
>>>>>>> e4258cdc0d298041d4477996327ae2a51c05c6f5
    <button class="navbar-toggle"
            aria-expanded="false"
            aria-controls="navbar-menu"
            aria-label="Buka menu navigasi">
<<<<<<< HEAD
        <span class="hamburger-icon" aria-hidden="true">
=======
        <span class="hamburger-icon">
>>>>>>> e4258cdc0d298041d4477996327ae2a51c05c6f5
            <span></span>
            <span></span>
            <span></span>
        </span>
    </button>
<<<<<<< HEAD
</nav>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" aria-hidden="true"></div>
=======

    <!-- MENU / SIDEBAR MOBILE -->
    <div class="navbar-menu" id="navbar-menu">

        <!-- Brand di dalam mobile sidebar -->
        <div class="mobile-sidebar-header">
            <div class="brand-icon"><i class="fas fa-snowflake"></i></div>
            <span class="brand-text">AC Servis Masjid</span>
        </div>

        <div class="nav-sep"></div>

        <a href="#home" 
           class="nav-link">
            <i class="fas fa-home"></i> Home
        </a>

        <a href="#keunggulan" class="nav-link ">
            <i class="fas fa-star"></i> Keunggulan
        </a>

        <a href="#harga" class="nav-link">
            <i class="fas fa-tag"></i> Harga
        </a>

        <a href="#kontak" class="nav-link">
            <i class="fas fa-phone"></i> Kontak
        </a>

        <div class="nav-sep"></div>

        <button class="btn-icon" onclick="toggleDarkMode()">
            <i class="fas fa-moon" id="darkModeIconGuest"></i>
            <span id="darkModeTextGuest">Mode Gelap</span>
        </button>

        <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-sign-in-alt"></i> Login
        </a>
    </div>
</nav>

<div class="mobile-menu-overlay"></div>
@endauth
>>>>>>> e4258cdc0d298041d4477996327ae2a51c05c6f5
