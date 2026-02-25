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
    localStorage.setItem('theme', isDark ? 'light' : 'dark');
}

(function () {
    const saved = localStorage.getItem('theme');
    if (saved) {
        document.documentElement.setAttribute('data-theme', saved);
        const icon = document.getElementById('darkModeIcon');
        if (icon) icon.className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
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

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    NavbarManager.init();
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
