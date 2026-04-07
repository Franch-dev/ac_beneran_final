/* ==========================================
   AC SERVIS MASJID — MAIN APP.JS
   ========================================== */

// === CSRF TOKEN ===
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

/**
 * Escape HTML-sensitive characters before rendering server data into templates.
 * @param {unknown} value
 * @returns {string}
 */
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

window.escapeHtml = escapeHtml;

function getOverlayElement() {
    return document.getElementById('overlay');
}

function syncPopupState() {
    const overlay = getOverlayElement();
    const hasOpenPopup = document.querySelectorAll('.popup.active').length > 0;

    if (overlay) {
        overlay.classList.toggle('active', hasOpenPopup);
    }

    document.body.classList.toggle('popup-open', hasOpenPopup);
    document.body.style.overflow = hasOpenPopup ? 'hidden' : '';
}

// === POPUP MANAGEMENT ===
function openPopup(id) {
    const popup = document.getElementById(id);
    if (!popup) {
        return;
    }

    popup.classList.add('active');
    syncPopupState();
}

function closePopup(id) {
    const popup = document.getElementById(id);
    if (!popup) {
        syncPopupState();
        return;
    }

    popup.classList.remove('active');

    if (popup.dataset.temporaryPopup === 'true') {
        setTimeout(() => popup.remove(), 180);
    }

    syncPopupState();
}

function closeAllPopups() {
    document.querySelectorAll('.popup').forEach((popup) => {
        popup.classList.remove('active');

        if (popup.dataset.temporaryPopup === 'true') {
            popup.remove();
        }
    });

    syncPopupState();
}

// === DARK MODE ===
function syncDarkModeUI(theme) {
    document.querySelectorAll(
        '#darkModeIcon, #darkModeIconMobile, #darkModeIconGuest'
    ).forEach(icon => {
        icon.className = theme === 'dark'
            ? 'fas fa-sun'
            : 'fas fa-moon';
    });

    ['darkModeText', 'darkModeTextGuest'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = theme === 'dark'
                ? 'Mode Terang'
                : 'Mode Gelap';
        }
    });
}

function applyTheme(theme, persist = true) {
    document.documentElement.setAttribute('data-theme', theme);
    if (persist) {
        localStorage.setItem('theme', theme);
    }
    syncDarkModeUI(theme);
}

function toggleDarkMode() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    applyTheme(isDark ? 'light' : 'dark');
}

window.toggleDarkMode = toggleDarkMode;

(function () {
    applyTheme(localStorage.getItem('theme') || 'light', false);
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
            return;
        }

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
        if (this.isOpen()) {
            this.close();
        } else {
            this.open();
        }
    },

    open() {
        if (!this.menu) return;

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

    close(focusToggle = true) {
        if (!this.menu) return;

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
        if (focusToggle) {
            this.toggleBtn.focus();
        }
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

window.closeGuestNavbar = function () {
    NavbarManager.close(false);
};

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
            this.mobileMenuBtn.setAttribute('aria-expanded', 'false');
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
        this.mobileMenuBtn?.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        window.pauseScroll?.();

        // Hide dark mode header
        const headerBtn = document.getElementById('headerDarkModeBtn');
        if (headerBtn) headerBtn.style.display = 'none';
    },

    closeMobile() {
        this.sidebar?.classList.remove('mobile-open');
        this.overlay?.classList.remove('active');
        this.mobileMenuBtn?.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        window.resumeScroll?.();

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

    syncDarkModeUI(localStorage.getItem('theme') || 'light');
    initStaggerReveal();

    if (document.querySelector('[data-status-badge]')) {
        refreshStatusBadges();
        window.setInterval(refreshStatusBadges, 20000);
    }
});

// === FETCH HELPER ===
async function apiFetch(url, method = 'GET', data = null) {
    const options = {
        method,
        credentials: 'same-origin',
        headers: {
            'Content-Type':  'application/json',
            'X-CSRF-TOKEN':  csrfToken,
            'Accept':        'application/json',
        }
    };
    if (data) options.body = JSON.stringify(data);

    const res = await fetch(url, options);
    const contentType = res.headers.get('content-type') || '';
    const json = contentType.includes('application/json')
        ? await res.json()
        : { message: 'Respons server tidak valid.' };

    if (!res.ok) {
        const err = new Error(json.message || 'Terjadi kesalahan.');
        err.status = res.status;
        err.data = json;
        throw err;
    }

    return json;
}

/**
 * Poll the monitoring counters used by the sidebar notification badges.
 *
 * @returns {Promise<void>}
 */
async function refreshStatusBadges() {
    const badges = document.querySelectorAll('[data-status-badge]');
    if (!badges.length) {
        return;
    }

    try {
        const counts = await apiFetch('/monitoring/status-counts');

        badges.forEach((badge) => {
            const status = badge.getAttribute('data-status-badge');
            const label = badge.getAttribute('data-badge-label') || status || 'Status';
            const total = Number(status ? counts?.[status] ?? 0 : 0);

            badge.textContent = String(total);
            badge.hidden = total < 1;
            badge.setAttribute('aria-label', `${label}: ${total}`);
        });
    } catch (error) {
        // Ignore polling failures to avoid noisy UX on non-monitoring flows.
    }
}

/**
 * Animate dashboard and monitoring surfaces with a cheap IntersectionObserver
 * instead of per-frame scroll handlers.
 */
function initStaggerReveal() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.querySelectorAll('[data-stagger-item]').forEach((item) => {
            item.classList.add('is-visible');
        });
        return;
    }

    const groups = document.querySelectorAll('[data-stagger-group]');
    if (!groups.length) {
        return;
    }

    const observer = new IntersectionObserver((entries, currentObserver) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            currentObserver.unobserve(entry.target);
        });
    }, {
        threshold: 0.16,
        rootMargin: '0px 0px -8% 0px',
    });

    groups.forEach((group) => {
        group.querySelectorAll('[data-stagger-item]').forEach((item, index) => {
            item.style.setProperty('--stagger-index', String(index));
            observer.observe(item);
        });
    });
}

// === TOAST NOTIFICATION ===
function legacyShowToast(message, type = 'success') {
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

function legacyShowToast(message, type = 'success') {
    const existing = document.querySelector('.toast-notification');
    if (existing) {
        existing.remove();
    }

    const tone = ['success', 'error', 'info', 'warning'].includes(type) ? type : 'info';
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${tone}`;
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');

    const icon = document.createElement('span');
    icon.className = 'toast-icon';
    icon.setAttribute('aria-hidden', 'true');

    const iconGlyph = document.createElement('i');
    iconGlyph.className = tone === 'success'
        ? 'fas fa-circle-check'
        : tone === 'error'
            ? 'fas fa-circle-xmark'
            : tone === 'warning'
                ? 'fas fa-triangle-exclamation'
                : 'fas fa-circle-info';
    icon.appendChild(iconGlyph);

    const content = document.createElement('div');
    content.className = 'toast-message';
    content.textContent = String(message ?? '');

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'toast-close';
    closeButton.setAttribute('aria-label', 'Tutup notifikasi');
    closeButton.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
    closeButton.addEventListener('click', () => toast.remove());

    toast.appendChild(icon);
    toast.appendChild(content);
    toast.appendChild(closeButton);
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('toast-notification--exit');
        window.setTimeout(() => toast.remove(), 220);
    }, 4000);
}

// === ESCAPE KEY ===
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAllPopups();
});
