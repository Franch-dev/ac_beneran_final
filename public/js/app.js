/* ==========================================
   AC SERVIS MASJID — MAIN APP.JS
   ========================================== */

// === CSRF TOKEN ===
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

// === POPUP MANAGEMENT ===
function openPopup(id) {
    document.getElementById(id)?.classList.add('active');
    document.getElementById('overlay')?.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePopup(id) {
    document.getElementById(id)?.classList.remove('active');
    const anyOpen = document.querySelectorAll('.popup.active').length > 0;
    if (!anyOpen) {
        document.getElementById('overlay')?.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function closeAllPopups() {
    document.querySelectorAll('.popup.active').forEach(p => p.classList.remove('active'));
    document.getElementById('overlay')?.classList.remove('active');
    document.body.style.overflow = '';
}

// === DARK MODE ===
function toggleDarkMode() {
    const html  = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    html.setAttribute('data-theme', isDark ? 'light' : 'dark');
    const icon = document.getElementById('darkModeIcon');
    if (icon) icon.className = isDark ? 'fas fa-moon' : 'fas fa-sun';
    const iconMobile = document.getElementById('darkModeIconMobile');
    if (iconMobile) iconMobile.className = isDark ? 'fas fa-moon' : 'fas fa-sun';
    const iconGuest = document.getElementById('darkModeIconGuest');
    if (iconGuest) iconGuest.className = isDark ? 'fas fa-moon' : 'fas fa-sun';
    localStorage.setItem('theme', isDark ? 'light' : 'dark');
}

(function () {
    const saved = localStorage.getItem('theme');
    if (saved) {
        document.documentElement.setAttribute('data-theme', saved);
        const icon = document.getElementById('darkModeIcon');
        if (icon) icon.className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        const iconMobile = document.getElementById('darkModeIconMobile');
        if (iconMobile) iconMobile.className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        const iconGuest = document.getElementById('darkModeIconGuest');
        if (iconGuest) iconGuest.className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
})();

// === NAVBAR / MOBILE MENU MANAGEMENT ===
const NavbarManager = {
    menu: null,
    toggleBtn: null,
    overlay: null,
    focusableElements: 'a[href], button, input, textarea, select, details, [tabindex]:not([tabindex="-1"])',
    firstFocusable: null,
    lastFocusable: null,

    init() {
        this.menu = document.querySelector('.navbar-menu');
        this.toggleBtn = document.querySelector('.navbar-toggle');
        this.overlay = document.querySelector('.mobile-menu-overlay');

        if (!this.menu || !this.toggleBtn) {
            console.warn('NavbarManager: Required elements not found', {
                menu: !!this.menu,
                toggleBtn: !!this.toggleBtn,
                overlay: !!this.overlay
            });
            return;
        }

        console.log('NavbarManager: Initialized successfully');

        // Bind events
        this.toggleBtn.addEventListener('click', () => this.toggle());

        // Close on overlay click
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.close());
        }

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });

        // Handle focus within menu
        this.menu.addEventListener('keydown', (e) => this.handleFocusTrap(e));

        // Close on resize to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024 && this.isOpen()) {
                this.close();
            }
        });
    },

    toggle() {
        console.log('NavbarManager: Toggle called, current state:', this.isOpen() ? 'open' : 'closed');
        if (this.isOpen()) {
            this.close();
        } else {
            this.open();
        }
    },

    open() {
        if (!this.menu) return;

        console.log('NavbarManager: Opening menu');
        this.menu.classList.add('open');
        document.body.classList.add('menu-open');

        // Update ARIA attributes
        this.toggleBtn.setAttribute('aria-expanded', 'true');
        this.toggleBtn.setAttribute('aria-label', 'Tutup menu navigasi');

        // Show overlay
        if (this.overlay) {
            this.overlay.classList.add('active');
        }

        // Store focus and move to first focusable element
        this.storeFocusableElements();
        setTimeout(() => {
            if (this.firstFocusable) {
                this.firstFocusable.focus();
            }
        }, 100);
    },

    close() {
        if (!this.menu) return;

        console.log('NavbarManager: Closing menu');
        this.menu.classList.remove('open');
        document.body.classList.remove('menu-open');

        // Update ARIA attributes
        this.toggleBtn.setAttribute('aria-expanded', 'false');
        this.toggleBtn.setAttribute('aria-label', 'Buka menu navigasi');

        // Hide overlay
        if (this.overlay) {
            this.overlay.classList.remove('active');
        }

        // Return focus to toggle button
        this.toggleBtn.focus();
    },

    isOpen() {
        return this.menu && this.menu.classList.contains('open');
    },

    storeFocusableElements() {
        const focusable = this.menu.querySelectorAll(this.focusableElements);
        this.firstFocusable = focusable[0];
        this.lastFocusable = focusable[focusable.length - 1];
    },

    handleFocusTrap(e) {
        if (e.key !== 'Tab') return;

        if (!this.firstFocusable || !this.lastFocusable) {
            this.storeFocusableElements();
        }

        // If shift+tab on first element, move to last
        if (e.shiftKey && document.activeElement === this.firstFocusable) {
            e.preventDefault();
            this.lastFocusable.focus();
        }
        // If tab on last element, move to first
        else if (!e.shiftKey && document.activeElement === this.lastFocusable) {
            e.preventDefault();
            this.firstFocusable.focus();
        }
    }
};

// Legacy function for backward compatibility
function toggleNavbar() {
    NavbarManager.toggle();
}

// ============================================
// SIDEBAR MANAGER
// ============================================
const SidebarManager = {
    sidebar: null,
    collapseBtn: null,
    mobileMenuBtn: null,
    overlay: null,

    init() {
        this.sidebar = document.getElementById('sidebar');
        this.collapseBtn = document.getElementById('sidebarCollapseBtn');
        this.mobileMenuBtn = document.getElementById('mobileMenuBtn');
        this.overlay = document.getElementById('sidebarOverlay');

        if (!this.sidebar) return; // Not a sidebar page

        // Load saved state
        const saved = localStorage.getItem('sidebarCollapsed');
        if (saved === 'true') {
            document.body.classList.add('sidebar-collapsed');
            const icon = document.getElementById('collapseIcon');
            if (icon) icon.className = 'fas fa-chevron-right';
        }

        // Desktop collapse toggle
        if (this.collapseBtn) {
            this.collapseBtn.addEventListener('click', () => this.toggleCollapse());
        }

        // Mobile toggle
        if (this.mobileMenuBtn) {
            this.mobileMenuBtn.addEventListener('click', () => this.toggleMobile());
        }

        // Overlay click close
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.closeMobile());
        }

        // Escape key close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeMobile();
        });

        // Add tooltips to links
        this.sidebar.querySelectorAll('.sidebar-link').forEach(link => {
            const label = link.querySelector('.sidebar-label');
            if (label) link.setAttribute('data-tooltip', label.textContent.trim());
        });
    },

    toggleCollapse() {
        const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
        const icon = document.getElementById('collapseIcon');
        if (icon) icon.className = isCollapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
    },

    openMobile() {
        this.sidebar?.classList.add('mobile-open');
        this.overlay?.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Hide dark mode header
        const headerBtn = document.getElementById('headerDarkModeBtn');
        if (headerBtn) headerBtn.style.display = 'none';
    },  

    closeMobile() {
        this.sidebar?.classList.remove('mobile-open');
        this.overlay?.classList.remove('active');
        document.body.style.overflow = '';

        // Show dark mode header
        const headerBtn = document.getElementById('headerDarkModeBtn');
        if (headerBtn) headerBtn.style.display = '';
    },

    toggleMobile() {
        const isOpen = this.sidebar?.classList.contains('mobile-open');

        if (isOpen) {
            this.closeMobile();
        } else {
            this.openMobile();
        }
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    NavbarManager.init();
    SidebarManager.init();

    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);

    // Sync text
    ['darkModeText', 'darkModeTextGuest'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = savedTheme === 'dark'
                ? 'Mode Terang'
                : 'Mode Gelap';
        }
    });

    // Sync icon
    document.querySelectorAll(
        '#darkModeIcon, #darkModeIconMobile, #darkModeIconGuest'
    ).forEach(icon => {
        icon.className = savedTheme === 'dark'
            ? 'fas fa-sun'
            : 'fas fa-moon';
    });
});

// === FETCH HELPER ===
async function apiFetch(url, method = 'GET', data = null) {
    const options = {
        method,
        headers: {
            'Content-Type':  'application/json',
            'X-CSRF-TOKEN':  csrfToken,
            'Accept':        'application/json',
        }
    };
    if (data) options.body = JSON.stringify(data);

    const res  = await fetch(url, options);
    const json = await res.json();

    if (!res.ok) {
        // Attach status HTTP dan full JSON response ke error object
        const err  = new Error(json.message || 'Terjadi kesalahan.');
        err.status = res.status;
        err.data   = json;          // berisi has_existing, existing_order, dll
        throw err;
    }

    return json;
}

// === TOAST NOTIFICATION ===
function showToast(message, type = 'success') {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = 'toast-notification toast-' + type;

    const bg = type === 'success' ? '#16a34a'
             : type === 'error'   ? '#dc2626'
             : type === 'info'    ? '#2563eb'
             : '#374151';

    toast.style.cssText =
        'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;' +
        'background:' + bg + ';color:#fff;padding:0.75rem 1.25rem;border-radius:8px;' +
        'box-shadow:0 4px 12px rgba(0,0,0,0.2);font-size:0.875rem;' +
        'display:flex;align-items:center;gap:0.5rem;' +
        'animation:slideIn 0.3s ease;max-width:360px;line-height:1.4;';

    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
    toast.innerHTML = icon + ' ' + message;
    document.body.appendChild(toast);

    setTimeout(function() {
        toast.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(function() { toast.remove(); }, 300);
    }, 4000);
}

// Animasi toast
const _toastStyle = document.createElement('style');
_toastStyle.textContent =
    '@keyframes slideIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }' +
    '@keyframes slideOut { to { transform: translateX(100px); opacity: 0; } }';
document.head.appendChild(_toastStyle);

// === ESCAPE KEY ===
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAllPopups();
});

// Dark mode icon + text sync
window.toggleDarkMode = function() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';

    const newTheme = isDark ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);

    // Sync semua icon
    document.querySelectorAll(
        '#darkModeIcon, #darkModeIconMobile, #darkModeIconGuest'
    ).forEach(icon => {
        icon.className = newTheme === 'dark'
            ? 'fas fa-sun'
            : 'fas fa-moon';
    });

    // Sync semua text
    const textMap = [
        'darkModeText',
        'darkModeTextGuest'
    ];

    textMap.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = newTheme === 'dark'
                ? 'Mode Terang'
                : 'Mode Gelap';
        }
    });
};

document.addEventListener("DOMContentLoaded", function () {
    const links = document.querySelectorAll(".nav-link");

    links.forEach(link => {
        link.addEventListener("click", function () {

            // Hapus semua active
            links.forEach(l => l.classList.remove("active"));

            // Tambah active ke yang diklik
            this.classList.add("active");
        });
    });
});


const mobileBtn = document.getElementById("mobileMenuBtn");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("sidebarOverlay");

mobileBtn.addEventListener("click", function () {
    const isOpen = this.getAttribute("aria-expanded") === "true";

    this.setAttribute("aria-expanded", !isOpen);
    sidebar.classList.toggle("active");
    overlay.classList.toggle("active");
});

/* ==========================================
   SCROLL SPY & REFRESH TO TOP
   ========================================== */

// 1. MEMAKSA REFRESH KE ATAS
if (history.scrollRestoration) {
    history.scrollRestoration = 'manual';
}
window.scrollTo(0, 0);

// 2. SCROLL SPY (Otomatis ganti menu saat scroll)
document.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');
    let lastActiveLink = null;

    const updateActiveNav = () => {
        const scrollPosition = window.pageYOffset + 100; // Offset for detection
        let currentSection = null;

<<<<<<< HEAD
        // Special handling for home section (at the very top)
        const homeSection = document.getElementById('home');
        if (homeSection && scrollPosition < homeSection.offsetHeight) {
            currentSection = 'home';
        } else {
            // Find the section that matches current scroll position
            sections.forEach((section) => {
                const sectionId = section.getAttribute("id");
                if (sectionId === 'home') return; // Skip home, already handled
                
                const sectionTop = section.offsetTop;
                const sectionBottom = sectionTop + section.offsetHeight;
                
                // If scroll is within this section, mark it as current
                if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                    currentSection = sectionId;
                }
            });

            // If no section found, use the last one before current scroll
            if (!currentSection) {
                for (let i = sections.length - 1; i >= 0; i--) {
                    const sectionId = sections[i].getAttribute("id");
                    if (scrollPosition >= sections[i].offsetTop) {
                        currentSection = sectionId;
                        break;
                    }
                }
            }
        }

        // Update nav links
        if (currentSection) {
            navLinks.forEach((link) => {
                const href = link.getAttribute("href");
                if (!href) return;
                
                const linkId = href.substring(1); // Remove # from href
                
                if (linkId === currentSection) {
                    if (lastActiveLink !== link) {
                        // Remove active from all links
                        navLinks.forEach(l => l.classList.remove("active"));
                        // Add active to current link
                        link.classList.add("active");
                        lastActiveLink = link;
                    }
                }
            });

            // Update URL
            if (window.location.hash !== `#${currentSection}`) {
                history.replaceState(null, null, `#${currentSection}`);
            }
        }
    };

    // Jalankan fungsi saat user scroll
    window.addEventListener('scroll', updateActiveNav, { passive: true });
    
    // Jalankan sekali saat halaman dimuat untuk sinkronisasi awal
    setTimeout(updateActiveNav, 100);
});

// 2. SCROLL SPY (Otomatis ganti menu saat scroll)
document.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');

    const updateActiveNav = () => {
        let current = "";
        const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;

        sections.forEach((section) => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
=======
        sections.forEach((section) => {
            const sectionTop = section.offsetTop;
>>>>>>> 4c5953a560297a4b795e6ab54c6cfe5cb24809ef
            
            // Jika posisi scroll sudah melewati batas atas section (dengan offset 150px)
            if (scrollPosition >= sectionTop - 150) {
                current = section.getAttribute("id");
            }
        });

        navLinks.forEach((link) => {
            link.classList.remove("active");
            // Ambil ID dari href (misal: "#keunggulan")
            const href = link.getAttribute("href");
            if (href && href.includes(`#${current}`)) {
                link.classList.add("active");
                
                // Update URL di address bar secara halus (tanpa loncat)
                if (current && window.location.hash !== `#${current}`) {
                    history.replaceState(null, null, `#${current}`);
                }
            }
        });
    };

    // Jalankan fungsi saat user scroll
    window.addEventListener('scroll', updateActiveNav);
    
    // Jalankan sekali saat halaman dimuat untuk sinkronisasi awal
    updateActiveNav();

    const counters = document.querySelectorAll('.counter');

counters.forEach(counter => {

    const target = parseFloat(counter.dataset.target);
    const isDecimal = target % 1 !== 0;

    let current = 0;

    const updateCounter = () => {

        const increment = target / 80;

        current += increment;

        if (current < target) {

            counter.innerText = isDecimal
                ? current.toFixed(1)
                : Math.floor(current);

            requestAnimationFrame(updateCounter);

        } else {

            counter.innerText = target;
        }
    };

    updateCounter();
});
});
