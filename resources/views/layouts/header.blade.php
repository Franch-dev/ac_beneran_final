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
            </button>
            <button class="btn-icon text-danger" onclick="openPopup('logoutPopup')" title="Logout" aria-label="Logout" role="menuitem">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
            </button>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm" role="menuitem">
                <i class="fas fa-sign-in-alt" aria-hidden="true"></i> Login
            </a>
        @endauth
    </div>

    <button class="navbar-toggle"
            aria-expanded="false"
            aria-controls="navbar-menu"
            aria-label="Buka menu navigasi">
        <span class="hamburger-icon" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </span>
    </button>
</nav>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" aria-hidden="true"></div>
