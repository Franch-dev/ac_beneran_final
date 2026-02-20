<nav class="navbar">
    <div class="navbar-brand">
        <div class="brand-icon"><i class="fas fa-snowflake"></i></div>
        <span>AC Servis Masjid</span>
    </div>
    <div class="navbar-menu">
        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="fas fa-home"></i> Home
        </a>

        @auth
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="{{ route('monitoring') }}" class="nav-link {{ request()->routeIs('monitoring') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i> Monitoring
            </a>
            <div class="nav-divider"></div>
            <div class="nav-user">
                <i class="fas fa-user-circle"></i>
                <span>{{ auth()->user()->name }}</span>
                <span class="role-badge role-{{ auth()->user()->role }}">{{ ucfirst(auth()->user()->role) }}</span>
            </div>
            <button class="btn-icon" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                <i class="fas fa-moon" id="darkModeIcon"></i>
            </button>
            <button class="btn-icon text-danger" onclick="openPopup('logoutPopup')" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
        @endauth
    </div>
    <button class="navbar-toggle" onclick="toggleNavbar()">
        <i class="fas fa-bars"></i>
    </button>
</nav>
