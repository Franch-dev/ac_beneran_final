const HTML_ESCAPE_MAP = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
    '`': '&#96;',
};

const ORDER_PROGRESS_MAP = {
    pending: {
        value: 18,
        label: 'Menunggu persetujuan',
        tone: 'warning',
    },
    approved: {
        value: 42,
        label: 'SPK diterbitkan',
        tone: 'success',
    },
    in_progress: {
        value: 68,
        label: 'Sedang dikerjakan',
        tone: 'primary',
    },
    waiting_invoice: {
        value: 84,
        label: 'Menunggu invoice',
        tone: 'info',
    },
    waiting_review: {
        value: 92,
        label: 'Menunggu review akhir',
        tone: 'accent',
    },
    completed: {
        value: 100,
        label: 'Selesai',
        tone: 'success',
    },
    cancelled: {
        value: 100,
        label: 'Dibatalkan',
        tone: 'danger',
    },
};

const pendingWriteRequests = new Map();
const GLOBAL_RUNTIME_NAMES = [
    'escapeHtml',
    'escapeAttribute',
    'formatDisplayDate',
    'getOrderProgress',
    'openPopup',
    'closePopup',
    'closeAllPopups',
    'openGuestOrderPopup',
    'toggleDarkMode',
    'showToast',
    'confirmAction',
    'openConfirmModal',
    'closeConfirmModal',
    'apiFetch',
    'refreshCurrentPageSnapshot',
    'scheduleCurrentPageSnapshot',
    'handleServicePortalAction',
];

let sharedRuntimeBooted = false;
let toastStylesInjected = false;
let globalClickBound = false;

function getWindow() {
    return typeof window !== 'undefined' ? window : null;
}

function getDocument() {
    return typeof document !== 'undefined' ? document : null;
}

function getStorage() {
    try {
        return getWindow()?.localStorage ?? null;
    } catch (_error) {
        return null;
    }
}

function getCsrfToken() {
    return getDocument()?.querySelector('meta[name="csrf-token"]')?.content || '';
}

export function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"'`]/g, (character) => HTML_ESCAPE_MAP[character]);
}

export function escapeAttribute(value) {
    return escapeHtml(value).replace(/\r?\n/g, ' ');
}

export function formatDisplayDate(value, locale = 'id-ID') {
    if (!value) {
        return '-';
    }

    const parsed = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return String(value);
    }

    return new Intl.DateTimeFormat(locale, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(parsed);
}

export function getOrderProgress(status) {
    if (typeof status !== 'string' || status.length === 0) {
        return {
            value: 8,
            label: 'Status belum tersedia',
            tone: 'neutral',
        };
    }

    return ORDER_PROGRESS_MAP[status] ?? {
        value: 12,
        label: 'Status belum dipetakan',
        tone: 'neutral',
    };
}

export function getSharedRuntimeGlobals() {
    return [...GLOBAL_RUNTIME_NAMES];
}

function getOverlayElement() {
    return getDocument()?.getElementById('overlay') ?? null;
}

function syncPopupState() {
    const doc = getDocument();
    if (!doc?.body) {
        return;
    }

    const overlay = getOverlayElement();
    const hasOpenPopup = doc.querySelectorAll('.popup.active').length > 0;

    if (overlay) {
        overlay.classList.toggle('active', hasOpenPopup);
    }

    doc.body.classList.toggle('popup-open', hasOpenPopup);
    doc.body.style.overflow = hasOpenPopup ? 'hidden' : '';
}

export function openPopup(id) {
    const popup = getDocument()?.getElementById(id);
    if (!popup) {
        return;
    }

    popup.classList.add('active');
    syncPopupState();
}

export function closePopup(id) {
    const popup = getDocument()?.getElementById(id);
    if (!popup) {
        syncPopupState();
        return;
    }

    popup.classList.remove('active');

    if (popup.dataset.temporaryPopup === 'true') {
        getWindow()?.setTimeout(() => popup.remove(), 180);
    }

    syncPopupState();
}

export function closeAllPopups() {
    const doc = getDocument();
    if (!doc) {
        return;
    }

    doc.querySelectorAll('.popup').forEach((popup) => {
        popup.classList.remove('active');

        if (popup.dataset.temporaryPopup === 'true') {
            popup.remove();
        }
    });

    syncPopupState();
}

export function openGuestOrderPopup(action, moduleLabel = 'AC Service') {
    const popup = getDocument()?.getElementById('guestOrderPopup');
    const form = popup?.querySelector('form');
    const hiddenAction = popup?.querySelector('#guest_order_action');
    const title = popup?.querySelector('.guest-order-popup-title');

    if (form && action) {
        form.action = action;
    }

    if (hiddenAction && action) {
        hiddenAction.value = action;
    }

    if (title) {
        title.textContent = `Formulir Service Order - ${moduleLabel}`;
    }

    openPopup('guestOrderPopup');
}

function activateRoleHubTab(tab) {
    const roleHub = tab.closest('[data-role-hub]');
    if (!roleHub) {
        return;
    }

    const targetPanel = tab.dataset.roleHubTab;
    if (!targetPanel) {
        return;
    }

    roleHub.querySelectorAll('[data-role-hub-tab]').forEach((button) => {
        const isActive = button === tab;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    roleHub.querySelectorAll('[data-role-hub-panel]').forEach((panel) => {
        panel.classList.toggle('is-active', panel.dataset.roleHubPanel === targetPanel);
    });
}

function closeInlineServiceForm(card) {
    if (!card) {
        return;
    }

    const type = card.dataset.serviceType || '';
    const inlineForm = card.querySelector('[data-service-card-form]');
    if (!inlineForm) {
        return;
    }

    inlineForm.hidden = true;
    card.classList.remove('is-expanded');

    if (type) {
        card.querySelectorAll(`[data-service-card-toggle="${type}"]`).forEach((button) => {
            button.setAttribute('aria-expanded', 'false');
        });
    }
}

function closeOtherInlineServiceForms(currentCard = null) {
    getDocument()?.querySelectorAll('[data-service-card]').forEach((card) => {
        if (card === currentCard) {
            return;
        }

        closeInlineServiceForm(card);
    });
}

function toggleInlineServiceForm(type, source) {
    const normalizedType = type === 'anggota' ? 'anggota' : 'masjid';
    const card = source?.closest('[data-service-card]');
    const inlineForm = card?.querySelector(`[data-service-card-form="${normalizedType}"]`);
    const triggerButtons = card?.querySelectorAll(`[data-service-card-toggle="${normalizedType}"]`) ?? [];

    if (!card || !inlineForm) {
        return;
    }

    const shouldOpen = inlineForm.hidden;
    closeOtherInlineServiceForms(shouldOpen ? card : null);

    inlineForm.hidden = !shouldOpen;
    card.classList.toggle('is-expanded', shouldOpen);

    triggerButtons.forEach((button) => {
        button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
    });

    if (!shouldOpen) {
        return;
    }

    const dateInput = inlineForm.querySelector('input[name="date"]');
    if (dateInput && !dateInput.value) {
        dateInput.value = dateInput.min || '';
    }

    inlineForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    inlineForm.querySelector('input[name="name"]')?.focus();
}

export function handleServicePortalAction(type, source = null) {
    const win = getWindow();
    const config = win?.SERVICE_PORTAL_CONFIG || {};

    if (config.authenticated) {
        const targetUrl = config.authRedirects?.[type];

        if (!targetUrl) {
            showToast('Tujuan dashboard belum dikonfigurasi.', 'error');
            return;
        }

        win.location.href = targetUrl;
        return;
    }

    toggleInlineServiceForm(type, source);
}

function syncDarkModeUI(theme) {
    getDocument()?.querySelectorAll('#darkModeIcon, #darkModeIconMobile, #darkModeIconGuest').forEach((icon) => {
        icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });

    ['darkModeText', 'darkModeTextGuest'].forEach((id) => {
        const element = getDocument()?.getElementById(id);
        if (element) {
            element.textContent = theme === 'dark' ? 'Mode Terang' : 'Mode Gelap';
        }
    });
}

function applyTheme(theme, persist = true) {
    const doc = getDocument();
    if (!doc) {
        return;
    }

    doc.documentElement.setAttribute('data-theme', theme);

    if (persist) {
        getStorage()?.setItem('theme', theme);
    }

    syncDarkModeUI(theme);
}

export function toggleDarkMode() {
    const currentTheme = getDocument()?.documentElement.getAttribute('data-theme');
    applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
}

function preferredTheme() {
    const win = getWindow();
    return win?.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

const NavbarManager = {
    menu: null,
    toggleBtn: null,
    overlay: null,
    focusableElements: 'a[href], button, input, textarea, select, details, [tabindex]:not([tabindex="-1"])',
    firstFocusable: null,
    lastFocusable: null,

    init() {
        const doc = getDocument();
        this.menu = doc?.querySelector('.navbar-menu') ?? null;
        this.toggleBtn = doc?.querySelector('.navbar-toggle') ?? null;
        this.overlay = doc?.querySelector('.mobile-menu-overlay') ?? null;

        if (!this.menu || !this.toggleBtn || this.toggleBtn.dataset.runtimeBound === 'true') {
            return;
        }

        this.toggleBtn.dataset.runtimeBound = 'true';
        this.toggleBtn.addEventListener('click', () => this.toggle());

        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.close());
        }

        doc?.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });

        this.menu.addEventListener('keydown', (event) => this.handleFocusTrap(event));

        getWindow()?.addEventListener('resize', () => {
            if ((getWindow()?.innerWidth ?? 0) > 1024 && this.isOpen()) {
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
        const doc = getDocument();
        if (!this.menu || !doc?.body) {
            return;
        }

        this.menu.classList.add('open');
        doc.body.classList.add('menu-open');
        this.toggleBtn?.setAttribute('aria-expanded', 'true');
        this.toggleBtn?.setAttribute('aria-label', 'Tutup menu navigasi');
        this.overlay?.classList.add('active');
        this.storeFocusableElements();

        getWindow()?.setTimeout(() => {
            this.firstFocusable?.focus();
        }, 100);
    },

    close(focusToggle = true) {
        const doc = getDocument();
        if (!this.menu || !doc?.body) {
            return;
        }

        this.menu.classList.remove('open');
        doc.body.classList.remove('menu-open');
        this.toggleBtn?.setAttribute('aria-expanded', 'false');
        this.toggleBtn?.setAttribute('aria-label', 'Buka menu navigasi');
        this.overlay?.classList.remove('active');

        if (focusToggle) {
            this.toggleBtn?.focus();
        }
    },

    isOpen() {
        return Boolean(this.menu?.classList.contains('open'));
    },

    storeFocusableElements() {
        if (!this.menu) {
            return;
        }

        const focusable = this.menu.querySelectorAll(this.focusableElements);
        this.firstFocusable = focusable[0] ?? null;
        this.lastFocusable = focusable[focusable.length - 1] ?? null;
    },

    handleFocusTrap(event) {
        if (event.key !== 'Tab') {
            return;
        }

        if (!this.firstFocusable || !this.lastFocusable) {
            this.storeFocusableElements();
        }

        if (event.shiftKey && getDocument()?.activeElement === this.firstFocusable) {
            event.preventDefault();
            this.lastFocusable?.focus();
        } else if (!event.shiftKey && getDocument()?.activeElement === this.lastFocusable) {
            event.preventDefault();
            this.firstFocusable?.focus();
        }
    },
};

const SidebarManager = {
    sidebar: null,
    collapseBtn: null,
    mobileMenuBtn: null,
    overlay: null,

    syncCollapseButton(isCollapsed = getDocument()?.body?.classList.contains('sidebar-collapsed') ?? false) {
        const doc = getDocument();
        const icon = doc?.getElementById('collapseIcon');
        const label = isCollapsed ? 'Buka sidebar' : 'Ciutkan sidebar';

        if (icon) {
            icon.className = isCollapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
        }

        if (this.collapseBtn) {
            this.collapseBtn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
            this.collapseBtn.setAttribute('aria-label', label);
            this.collapseBtn.setAttribute('title', label);
            this.collapseBtn.setAttribute('data-tooltip', label);
        }
    },

    init() {
        const doc = getDocument();
        this.sidebar = doc?.getElementById('sidebar') ?? null;
        this.collapseBtn = doc?.getElementById('sidebarCollapseBtn') ?? null;
        this.mobileMenuBtn = doc?.getElementById('mobileMenuBtn') ?? null;
        this.overlay = doc?.getElementById('sidebarOverlay') ?? null;

        if (!this.sidebar || this.sidebar.dataset.runtimeBound === 'true') {
            return;
        }

        this.sidebar.dataset.runtimeBound = 'true';

        if (getStorage()?.getItem('sidebarCollapsed') === 'true') {
            doc?.body?.classList.add('sidebar-collapsed');
        }

        this.syncCollapseButton();

        if (this.collapseBtn) {
            this.collapseBtn.addEventListener('click', () => this.toggleCollapse());
        }

        if (this.mobileMenuBtn) {
            this.mobileMenuBtn.setAttribute('aria-expanded', 'false');
            this.mobileMenuBtn.addEventListener('click', () => this.toggleMobile());
        }

        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.closeMobile());
        }

        doc?.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.closeMobile();
            }
        });

        this.sidebar.querySelectorAll('.sidebar-link').forEach((link) => {
            const label = link.querySelector('.sidebar-label');
            if (label) {
                link.setAttribute('data-tooltip', label.textContent.trim());
            }
        });
    },

    toggleCollapse() {
        const doc = getDocument();
        const isCollapsed = doc?.body?.classList.toggle('sidebar-collapsed') ?? false;
        getStorage()?.setItem('sidebarCollapsed', String(isCollapsed));

        this.syncCollapseButton(isCollapsed);
    },

    openMobile() {
        const doc = getDocument();
        this.sidebar?.classList.add('mobile-open');
        this.overlay?.classList.add('active');
        this.mobileMenuBtn?.setAttribute('aria-expanded', 'true');

        if (doc?.body) {
            doc.body.style.overflow = 'hidden';
        }

        getWindow()?.pauseScroll?.();

        const headerBtn = doc?.getElementById('headerDarkModeBtn');
        if (headerBtn) {
            headerBtn.style.display = 'none';
        }
    },

    closeMobile() {
        const doc = getDocument();
        this.sidebar?.classList.remove('mobile-open');
        this.overlay?.classList.remove('active');
        this.mobileMenuBtn?.setAttribute('aria-expanded', 'false');

        if (doc?.body) {
            doc.body.style.overflow = '';
        }

        getWindow()?.resumeScroll?.();

        const headerBtn = doc?.getElementById('headerDarkModeBtn');
        if (headerBtn) {
            headerBtn.style.display = '';
        }
    },

    toggleMobile() {
        if (this.sidebar?.classList.contains('mobile-open')) {
            this.closeMobile();
        } else {
            this.openMobile();
        }
    },
};

function initRevealMotion() {
    const doc = getDocument();
    const win = getWindow();
    if (!doc || !win) {
        return;
    }

    const revealTargets = Array.from(doc.querySelectorAll('.ui-reveal'));
    if (!revealTargets.length) {
        return;
    }

    if (win.matchMedia?.('(prefers-reduced-motion: reduce)').matches || typeof win.IntersectionObserver === 'undefined') {
        revealTargets.forEach((element) => element.classList.add('is-visible'));
        return;
    }

    const observer = new win.IntersectionObserver((entries, currentObserver) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            currentObserver.unobserve(entry.target);
        });
    }, {
        threshold: 0.16,
        root: null,
        rootMargin: '0px 0px -8% 0px',
    });

    revealTargets.forEach((element, index) => {
        element.style.setProperty('--ui-reveal-delay', `${Math.min(index * 60, 280)}ms`);
        observer.observe(element);
    });
}

function initStaggerReveal() {
    const doc = getDocument();
    const win = getWindow();
    if (!doc || !win) {
        return;
    }

    if (win.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
        doc.querySelectorAll('[data-stagger-item]').forEach((item) => {
            item.classList.add('is-visible');
        });
        return;
    }

    const groups = doc.querySelectorAll('[data-stagger-group]');
    if (!groups.length || typeof win.IntersectionObserver === 'undefined') {
        return;
    }

    const observer = new win.IntersectionObserver((entries, currentObserver) => {
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

function formatCounterValue(value, decimals) {
    return decimals ? value.toFixed(1) : String(Math.round(value));
}

function animateCounter(counter) {
    if (!counter || counter.dataset.counterInitialized === 'true') {
        return;
    }

    counter.dataset.counterInitialized = 'true';

    const rawTarget = counter.getAttribute('data-target') || '0';
    const target = parseFloat(rawTarget) || 0;
    const decimals = rawTarget.includes('.') ? 1 : 0;
    const win = getWindow();

    if (!win?.requestAnimationFrame) {
        counter.textContent = formatCounterValue(target, decimals);
        return;
    }

    const duration = 900;
    const startTime = win.performance?.now?.() ?? Date.now();
    const easeOutCubic = (progress) => 1 - Math.pow(1 - progress, 3);

    function update(time) {
        const elapsed = Math.min(((time ?? Date.now()) - startTime) / duration, 1);
        const value = target * easeOutCubic(elapsed);
        counter.textContent = formatCounterValue(value, decimals);

        if (elapsed < 1) {
            win.requestAnimationFrame(update);
        }
    }

    win.requestAnimationFrame(update);
}

function initCounters(root = getDocument()) {
    const doc = getDocument();
    const win = getWindow();
    const counters = root?.querySelectorAll?.('.counter') ?? [];

    if (!counters.length) {
        return;
    }

    if (!win?.IntersectionObserver) {
        counters.forEach((counter) => animateCounter(counter));
        return;
    }

    const observer = new win.IntersectionObserver((entries, currentObserver) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            animateCounter(entry.target);
            currentObserver.unobserve(entry.target);
        });
    }, { threshold: 0.2 });

    counters.forEach((counter) => {
        if (counter.dataset.counterInitialized === 'true') {
            return;
        }

        if (doc?.documentElement.contains(counter)) {
            observer.observe(counter);
        }
    });
}

async function parseResponsePayload(response) {
    const contentType = response.headers?.get?.('content-type') || '';

    if (contentType.includes('application/json')) {
        return response.json().catch(() => ({ message: 'Respons server tidak valid.' }));
    }

    const text = await response.text().catch(() => '');
    return text.length > 0 ? { message: text } : { message: 'Respons server tidak valid.' };
}

export async function apiFetch(url, method = 'GET', data = null) {
    const win = getWindow();
    const requestMethod = String(method || 'GET').toUpperCase();
    const requestKey = requestMethod === 'GET' ? null : `${requestMethod}:${url}`;

    if (requestKey && pendingWriteRequests.has(requestKey)) {
        return pendingWriteRequests.get(requestKey);
    }

    const request = (async () => {
        const response = await fetch(url, {
            method: requestMethod,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: data ? JSON.stringify(data) : undefined,
        });

        const payload = await parseResponsePayload(response);

        if (!response.ok) {
            if (response.status === 401) {
                showToast('Sesi kadaluarsa. Memuat ulang halaman...', 'warning');

                try {
                    await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
                } catch (_error) {
                    // Reload is still the fallback even if the CSRF refresh endpoint is unavailable.
                }

                win?.setTimeout(() => win.location.reload(), 1500);

                const error = new Error('Session expired');
                error.status = 401;
                throw error;
            }

            const error = new Error(payload?.message || `Error ${response.status}`);
            error.status = response.status;
            error.data = payload;
            throw error;
        }

        return payload;
    })().catch((error) => {
        if (error.status !== 401) {
            throw error;
        }

        return null;
    }).finally(() => {
        if (requestKey) {
            pendingWriteRequests.delete(requestKey);
        }
    });

    if (requestKey) {
        pendingWriteRequests.set(requestKey, request);
    }

    return request;
}

export async function refreshStatusBadges() {
    const doc = getDocument();
    if (!doc) {
        return;
    }

    const badges = doc.querySelectorAll('[data-status-badge]');
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
    } catch (_error) {
        // Ignore badge refresh failures on pages that do not use monitoring data.
    }
}

const PageSyncManager = {
    init() {},

    async refreshCurrentPageSnapshot(force = false) {
        const win = getWindow();
        const doc = getDocument();
        const config = win?.PAGE_SYNC_CONFIG;

        if (!win || !doc || !config?.snapshotRoute || !config?.rootSelector) {
            return false;
        }

        const currentRoot = doc.querySelector(config.rootSelector);
        if (!currentRoot) {
            return false;
        }

        try {
            const snapshotUrl = new URL(config.snapshotRoute, win.location.origin);
            const params = new URLSearchParams(win.location.search);
            params.forEach((value, key) => snapshotUrl.searchParams.set(key, value));

            const payload = await apiFetch(snapshotUrl.toString());
            const parser = new win.DOMParser();
            const nextDocument = parser.parseFromString(payload?.html || '', 'text/html');
            const nextRoot = nextDocument.querySelector(config.rootSelector);

            if (!nextRoot) {
                return false;
            }

            currentRoot.innerHTML = nextRoot.innerHTML;

            if (Array.isArray(config.persistentSelectors)) {
                config.persistentSelectors.forEach((selector) => {
                    const currentNode = doc.querySelector(selector);
                    const nextNode = nextDocument.querySelector(selector);

                    if (!currentNode || !nextNode || currentNode.classList.contains('active')) {
                        return;
                    }

                    currentNode.innerHTML = nextNode.innerHTML;
                });
            }

            initRevealMotion();
            initStaggerReveal();
            initCounters(currentRoot);
            await refreshStatusBadges();

            if (typeof win[config.afterRender] === 'function') {
                win[config.afterRender]();
            }

            doc.dispatchEvent(new win.CustomEvent('ac-sync:rendered'));

            return true;
        } catch (error) {
            if (force) {
                throw error;
            }

            return false;
        }
    },
};

function ensureToastStyles() {
    const doc = getDocument();
    if (!doc?.head || toastStylesInjected) {
        return;
    }

    const style = doc.createElement('style');
    style.textContent = [
        '@keyframes slideIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }',
        '@keyframes slideOut { to { transform: translateX(100px); opacity: 0; } }',
    ].join('');
    doc.head.appendChild(style);
    toastStylesInjected = true;
}

export function showToast(message, type = 'success') {
    const doc = getDocument();
    const win = getWindow();
    if (!doc?.body || !win) {
        return;
    }

    ensureToastStyles();

    doc.querySelector('.toast-notification')?.remove();

    const tone = ['success', 'error', 'info', 'warning'].includes(type) ? type : 'info';
    const toast = doc.createElement('div');
    toast.className = `toast-notification toast-${tone}`;
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');

    const icon = doc.createElement('span');
    icon.className = 'toast-icon';
    icon.setAttribute('aria-hidden', 'true');

    const iconGlyph = doc.createElement('i');
    iconGlyph.className = tone === 'success'
        ? 'fas fa-circle-check'
        : tone === 'error'
            ? 'fas fa-circle-xmark'
            : tone === 'warning'
                ? 'fas fa-triangle-exclamation'
                : 'fas fa-circle-info';
    icon.appendChild(iconGlyph);

    const content = doc.createElement('div');
    content.className = 'toast-message';
    content.textContent = String(message ?? '');

    const closeButton = doc.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'toast-close';
    closeButton.setAttribute('aria-label', 'Tutup notifikasi');
    closeButton.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
    closeButton.addEventListener('click', () => toast.remove());

    toast.appendChild(icon);
    toast.appendChild(content);
    toast.appendChild(closeButton);
    doc.body.appendChild(toast);

    win.setTimeout(() => {
        toast.classList.add('toast-notification--exit');
        win.setTimeout(() => toast.remove(), 220);
    }, 4000);
}

function bindGlobalClickHandlers() {
    const doc = getDocument();
    if (!doc || globalClickBound) {
        return;
    }

    globalClickBound = true;
    doc.addEventListener('click', (event) => {
        const roleHubTab = event.target.closest?.('[data-role-hub-tab]');
        if (roleHubTab) {
            activateRoleHubTab(roleHubTab);
            return;
        }

        const closeInlineButton = event.target.closest?.('[data-service-card-close]');
        if (closeInlineButton) {
            closeInlineServiceForm(closeInlineButton.closest('[data-service-card]'));
        }
    });
}

function bootSharedUiRuntime() {
    applyTheme(getStorage()?.getItem('theme') || preferredTheme(), false);
    NavbarManager.init();
    SidebarManager.init();
    bindGlobalClickHandlers();
    initRevealMotion();
    initStaggerReveal();
    initCounters();
    refreshStatusBadges();
}

export function registerUiRuntime() {
    const win = getWindow();
    const doc = getDocument();

    if (!win) {
        return;
    }

    win.AppUI = Object.assign(win.AppUI || {}, {
        escapeHtml,
        escapeAttribute,
        formatDisplayDate,
        getOrderProgress,
        openPopup,
        closePopup,
        closeAllPopups,
        openGuestOrderPopup,
        toggleDarkMode,
        showToast,
        confirmAction: win.confirmAction,
        openConfirmModal: win.openConfirmModal,
        closeConfirmModal: win.closeConfirmModal,
        apiFetch,
        refreshStatusBadges,
        refreshCurrentPageSnapshot: () => PageSyncManager.refreshCurrentPageSnapshot(true),
        scheduleCurrentPageSnapshot: () => undefined,
    });

    win.escapeHtml = escapeHtml;
    win.escapeAttribute = escapeAttribute;
    win.formatDisplayDate = formatDisplayDate;
    win.getOrderProgress = getOrderProgress;
    win.handleServicePortalAction = handleServicePortalAction;
    win.openPopup = openPopup;
    win.closePopup = closePopup;
    win.closeAllPopups = closeAllPopups;
    win.openGuestOrderPopup = win.openGuestOrderPopup || openGuestOrderPopup;
    win.toggleDarkMode = toggleDarkMode;
    win.showToast = showToast;
    win.apiFetch = apiFetch;
    win.refreshCurrentPageSnapshot = () => PageSyncManager.refreshCurrentPageSnapshot(true);
    win.scheduleCurrentPageSnapshot = () => undefined;
    win.closeGuestNavbar = () => NavbarManager.close(false);

    if (sharedRuntimeBooted) {
        if (doc && doc.readyState !== 'loading') {
            bootSharedUiRuntime();
        }

        return;
    }

    sharedRuntimeBooted = true;

    if (!doc) {
        return;
    }

    if (doc.readyState === 'loading') {
        doc.addEventListener('DOMContentLoaded', bootSharedUiRuntime, { once: true });
        return;
    }

    bootSharedUiRuntime();
}
