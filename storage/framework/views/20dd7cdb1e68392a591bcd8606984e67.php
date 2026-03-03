<?php if(auth()->guard()->check()): ?>
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
        <a href="<?php echo e(route('home')); ?>" class="sidebar-link <?php echo e(request()->routeIs('home') ? 'active' : ''); ?>" role="menuitem">
            <span class="sidebar-icon"><i class="fas fa-home"></i></span>
            <span class="sidebar-label">Home</span>
        </a>
        <a href="<?php echo e(route('dashboard')); ?>" class="sidebar-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>" role="menuitem">
            <span class="sidebar-icon"><i class="fas fa-th-large"></i></span>
            <span class="sidebar-label">Dashboard</span>
        </a>
        <a href="<?php echo e(route('monitoring')); ?>" class="sidebar-link <?php echo e(request()->routeIs('monitoring') ? 'active' : ''); ?>" role="menuitem">
            <span class="sidebar-icon"><i class="fas fa-chart-line"></i></span>
            <span class="sidebar-label">Monitoring</span>
        </a>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-divider"></div>
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?php echo e(strtoupper(substr(auth()->user()->name, 0, 1))); ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?php echo e(auth()->user()->name); ?></div>
                <span class="role-badge role-<?php echo e(auth()->user()->role); ?>"><?php echo e(ucfirst(auth()->user()->role)); ?></span>
            </div>
        </div>
        <div class="sidebar-actions">
            <button class="sidebar-action-btn" onclick="toggleDarkMode()" title="Toggle Dark Mode" aria-label="Ganti mode gelap/terang">
                <i class="fas fa-moon" id="darkModeIcon"></i>
                <span class="sidebar-label">Mode Gelap</span>
            </button>
            <button class="sidebar-action-btn danger" onclick="openPopup('logoutPopup')" title="Logout" aria-label="Logout">
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
    <button class="btn-icon" onclick="toggleDarkMode()" title="Toggle Dark Mode">
        <i class="fas fa-moon" id="darkModeIconMobile"></i>
    </button>
</header>

<!-- Mobile Overlay -->
<div class="sidebar-mobile-overlay" id="sidebarOverlay"></div>

<?php else: ?>
<!-- TOP NAVBAR (for guests/landing page) -->
<nav class="navbar" role="navigation" aria-label="Navigasi utama">
    <a href="#main-content" class="skip-link">Langsung ke konten utama</a>

    <div class="navbar-brand">
        <div class="brand-icon" aria-hidden="true"><i class="fas fa-snowflake"></i></div>
        <span>AC Servis Masjid</span>
    </div>

    <div class="navbar-menu" id="navbar-menu" role="menubar">
        <a href="<?php echo e(route('home')); ?>" class="nav-link <?php echo e(request()->routeIs('home') ? 'active' : ''); ?>" role="menuitem">
            <i class="fas fa-home" aria-hidden="true"></i> Home
        </a>
        <a href="#keunggulan" class="nav-link" role="menuitem">
            <i class="fas fa-star" aria-hidden="true"></i> Keunggulan
        </a>
        <a href="#harga" class="nav-link" role="menuitem">
            <i class="fas fa-tag" aria-hidden="true"></i> Harga
        </a>
        <a href="#kontak" class="nav-link" role="menuitem">
            <i class="fas fa-phone" aria-hidden="true"></i> Kontak
        </a>
        <div class="nav-sep" role="separator"></div>
        <button class="btn-icon" onclick="toggleDarkMode()" title="Toggle Dark Mode" aria-label="Ganti mode gelap/terang">
            <i class="fas fa-moon" id="darkModeIcon" aria-hidden="true"></i>
        </button>
        <a href="<?php echo e(route('login')); ?>" class="btn btn-primary btn-sm" role="menuitem">
            <i class="fas fa-sign-in-alt" aria-hidden="true"></i> Login
        </a>
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
<?php endif; ?>
<?php /**PATH C:\Users\Hype G12\ac_beneran_final\resources\views/layouts/header.blade.php ENDPATH**/ ?>