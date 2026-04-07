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

/**
 * Escape untrusted text before injecting it into HTML.
 *
 * @param {unknown} value
 * @returns {string}
 */
export function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"'`]/g, (character) => HTML_ESCAPE_MAP[character]);
}

/**
 * Escape text intended for attribute values.
 *
 * @param {unknown} value
 * @returns {string}
 */
export function escapeAttribute(value) {
    return escapeHtml(value).replace(/\r?\n/g, ' ');
}

/**
 * Format a date for Indonesian UI surfaces.
 *
 * @param {string | number | Date | null | undefined} value
 * @param {string} locale
 * @returns {string}
 */
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

/**
 * Resolve the visual workflow progress for a service order status.
 *
 * @param {string | null | undefined} status
 * @returns {{ value: number, label: string, tone: string }}
 */
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

function initRevealMotion() {
    if (typeof document === 'undefined') {
        return;
    }

    const revealTargets = Array.from(document.querySelectorAll('.ui-reveal'));
    if (!revealTargets.length) {
        return;
    }

    const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion || typeof IntersectionObserver === 'undefined') {
        revealTargets.forEach((element) => element.classList.add('is-visible'));
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
        root: null,
        rootMargin: '0px 0px -8% 0px',
    });

    revealTargets.forEach((element, index) => {
        element.style.setProperty('--ui-reveal-delay', `${Math.min(index * 60, 280)}ms`);
        observer.observe(element);
    });
}

/**
 * Expose shared UI helpers to legacy page scripts.
 *
 * @returns {void}
 */
export function registerUiRuntime() {
    if (typeof window === 'undefined') {
        return;
    }

    window.AppUI = Object.assign(window.AppUI || {}, {
        escapeHtml,
        escapeAttribute,
        formatDisplayDate,
        getOrderProgress,
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRevealMotion, { once: true });
        return;
    }

    initRevealMotion();
}
