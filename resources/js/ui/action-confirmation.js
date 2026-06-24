const OVERLAY_ID = 'confirm-modal-overlay';
const LEGACY_STATIC_CONFIRM_ID = 'confirmModal';
const VALID_TONES = ['success', 'danger', 'warning', 'info', 'primary'];

let activeResolve = null;
let activeOptions = null;
let lastSubmitter = null;
let bound = false;

function getDocument() {
    return typeof document !== 'undefined' ? document : null;
}

function getWindow() {
    return typeof window !== 'undefined' ? window : null;
}

function normalizeTone(type) {
    return VALID_TONES.includes(type) ? type : 'primary';
}

function asText(value, fallback = '') {
    const normalized = value ?? fallback;
    return String(normalized);
}

function buildElement(tag, className = '', text = null) {
    const doc = getDocument();
    const element = doc.createElement(tag);

    if (className) {
        element.className = className;
    }

    if (text !== null && text !== undefined) {
        element.textContent = String(text);
    }

    return element;
}

function formatDetailRows(options = {}) {
    if (Array.isArray(options.details)) {
        return options.details
            .map((detail) => ({
                label: detail?.label ?? detail?.name ?? '',
                value: detail?.value ?? '',
            }))
            .filter((detail) => detail.label || detail.value);
    }

    if (options.details && typeof options.details === 'object') {
        return Object.entries(options.details).map(([label, value]) => ({ label, value }));
    }

    if (options.orderData) {
        return [
            { label: 'No. Order', value: options.orderData.orderNumber ?? '-' },
            { label: 'Masjid', value: options.orderData.masjidName ?? '-' },
            { label: 'Tanggal', value: options.orderData.serviceDate ?? '-' },
        ];
    }

    return [];
}

function closeLegacyStaticConfirm() {
    const legacyConfirm = getDocument()?.getElementById(LEGACY_STATIC_CONFIRM_ID);
    if (legacyConfirm) {
        legacyConfirm.classList.remove('active');
    }
}

function createOverlay() {
    const doc = getDocument();
    let overlay = doc.getElementById(OVERLAY_ID);

    if (overlay) {
        return overlay;
    }

    overlay = buildElement('div', 'modal-overlay action-confirm-overlay');
    overlay.id = OVERLAY_ID;
    overlay.setAttribute('role', 'presentation');
    overlay.innerHTML = `
        <div class="modal-container action-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="action-confirm-title">
            <div class="modal-header action-confirm-header">
                <span class="action-confirm-icon" aria-hidden="true"><i class="fas fa-circle-question"></i></span>
                <h3 id="action-confirm-title" class="modal-heading action-confirm-title"></h3>
                <button type="button" class="modal-close action-confirm-close" aria-label="Tutup">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body action-confirm-body">
                <p class="modal-message action-confirm-message"></p>
                <div class="modal-order-context action-confirm-details" hidden></div>
                <div class="action-confirm-fields" hidden></div>
            </div>
            <div class="modal-footer action-confirm-footer">
                <button type="button" class="btn btn-secondary modal-cancel action-confirm-cancel">Batal</button>
                <button type="button" class="btn modal-confirm action-confirm-submit">Ya, Lanjutkan</button>
            </div>
        </div>
    `;

    doc.body.appendChild(overlay);

    overlay.querySelector('.action-confirm-close')?.addEventListener('click', () => closeConfirmModal(false));
    overlay.querySelector('.action-confirm-cancel')?.addEventListener('click', () => closeConfirmModal(false));
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeConfirmModal(false);
        }
    });

    doc.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && overlay.classList.contains('active')) {
            closeConfirmModal(false);
        }
    });

    return overlay;
}

function renderDetails(container, rows) {
    container.replaceChildren();
    container.hidden = rows.length === 0;

    rows.forEach((row) => {
        const detail = buildElement('div', 'action-confirm-detail');
        detail.appendChild(buildElement('span', 'action-confirm-detail-label', row.label));
        detail.appendChild(buildElement('strong', 'action-confirm-detail-value', row.value || '-'));
        container.appendChild(detail);
    });
}

function renderFields(container, fields = []) {
    container.replaceChildren();
    container.hidden = fields.length === 0;

    fields.forEach((field) => {
        const fieldWrap = buildElement('label', 'action-confirm-field');
        const label = buildElement('span', 'action-confirm-field-label', field.label || field.name || 'Input');
        const input = buildElement(field.type === 'textarea' ? 'textarea' : 'input', 'form-input action-confirm-input');
        input.name = field.name || 'value';
        input.required = Boolean(field.required);

        if (field.type && field.type !== 'textarea') {
            input.type = field.type;
        }

        if (field.placeholder) {
            input.placeholder = field.placeholder;
        }

        if (field.value !== undefined && field.value !== null) {
            input.value = String(field.value);
        }

        if (field.rows && input.tagName === 'TEXTAREA') {
            input.rows = Number(field.rows);
        }

        fieldWrap.append(label, input);
        container.appendChild(fieldWrap);
    });
}

function readFields(overlay) {
    const values = {};
    const invalid = [];

    overlay.querySelectorAll('.action-confirm-input').forEach((field) => {
        const value = field.value;
        values[field.name] = value;

        if (field.required && !String(value).trim()) {
            invalid.push(field);
        }
    });

    if (invalid.length) {
        invalid[0].focus();
        invalid[0].classList.add('is-invalid');
        invalid[0].addEventListener('input', () => invalid[0].classList.remove('is-invalid'), { once: true });
        return { ok: false, values };
    }

    return { ok: true, values };
}

function renderOverlay(overlay, options = {}) {
    const tone = normalizeTone(options.type || options.tone);
    const modal = overlay.querySelector('.action-confirm-modal');
    const title = overlay.querySelector('.action-confirm-title');
    const message = overlay.querySelector('.action-confirm-message');
    const icon = overlay.querySelector('.action-confirm-icon i');
    const cancelButton = overlay.querySelector('.action-confirm-cancel');
    const confirmButton = overlay.querySelector('.action-confirm-submit');
    const details = overlay.querySelector('.action-confirm-details');
    const fields = overlay.querySelector('.action-confirm-fields');

    modal.className = `modal-container action-confirm-modal modal-${tone} action-confirm-${tone}`;
    title.textContent = asText(options.heading || options.title, 'Konfirmasi');
    message.textContent = asText(options.message, 'Lanjutkan aksi ini?');

    icon.className = tone === 'danger'
        ? 'fas fa-triangle-exclamation'
        : tone === 'success'
            ? 'fas fa-circle-check'
            : tone === 'warning'
                ? 'fas fa-circle-exclamation'
                : 'fas fa-circle-question';

    cancelButton.hidden = options.showCancel === false;
    cancelButton.textContent = asText(options.cancelText, 'Batal');
    confirmButton.textContent = asText(options.confirmText, options.showCancel === false ? 'Tutup' : 'Ya, Lanjutkan');
    confirmButton.className = `btn modal-confirm action-confirm-submit btn-${tone}`;
    confirmButton.disabled = false;

    renderDetails(details, formatDetailRows(options));
    renderFields(fields, options.fields || []);
}

function focusFirstControl(overlay) {
    const firstInput = overlay.querySelector('.action-confirm-input');
    const confirmButton = overlay.querySelector('.action-confirm-submit');
    getWindow()?.setTimeout(() => (firstInput || confirmButton)?.focus(), 30);
}

async function handleConfirmClick(overlay) {
    const confirmButton = overlay.querySelector('.action-confirm-submit');
    const { ok, values } = readFields(overlay);

    if (!ok) {
        return;
    }

    const options = activeOptions || {};
    confirmButton.disabled = true;

    closeConfirmModal({ confirmed: true, values });

    if (typeof options.onConfirm === 'function') {
        await options.onConfirm(values);
    }
}

export function closeConfirmModal(result = false) {
    const overlay = getDocument()?.getElementById(OVERLAY_ID);

    if (overlay) {
        overlay.classList.remove('active');
        overlay.querySelector('.action-confirm-submit')?.replaceWith(overlay.querySelector('.action-confirm-submit').cloneNode(true));
    }

    closeLegacyStaticConfirm();

    if (typeof activeResolve === 'function') {
        activeResolve(result);
    }

    activeResolve = null;
    activeOptions = null;
}

export function confirmAction(options = {}) {
    const doc = getDocument();

    if (!doc?.body) {
        return Promise.resolve(false);
    }

    const overlay = createOverlay();
    closeConfirmModal(false);

    const confirmButton = overlay.querySelector('.action-confirm-submit');
    const freshConfirmButton = confirmButton.cloneNode(true);
    confirmButton.parentNode.replaceChild(freshConfirmButton, confirmButton);

    activeOptions = { ...options };
    renderOverlay(overlay, activeOptions);

    freshConfirmButton.addEventListener('click', () => {
        handleConfirmClick(overlay).catch((error) => {
            freshConfirmButton.disabled = false;
            getWindow()?.showToast?.(error?.message || 'Aksi gagal dijalankan.', 'error');
        });
    });

    overlay.classList.add('active');
    focusFirstControl(overlay);

    return new Promise((resolve) => {
        activeResolve = resolve;
    });
}

function optionsFromElement(element) {
    const dataset = element?.dataset || {};

    return {
        type: dataset.confirmType || dataset.confirmTone || 'warning',
        heading: dataset.confirmHeading || dataset.confirmTitle || 'Konfirmasi',
        message: dataset.confirmMessage || dataset.confirm || 'Lanjutkan aksi ini?',
        confirmText: dataset.confirmText || 'Ya, Lanjutkan',
        cancelText: dataset.confirmCancelText || 'Batal',
    };
}

function isConfirmedForm(form) {
    return form?.dataset.actionConfirmed === 'true';
}

function markFormConfirmed(form) {
    form.dataset.actionConfirmed = 'true';
}

function unmarkFormConfirmed(form) {
    getWindow()?.setTimeout(() => {
        if (form) {
            delete form.dataset.actionConfirmed;
        }
    }, 0);
}

function bindDeclarativeConfirmations() {
    const doc = getDocument();

    if (!doc || bound) {
        return;
    }

    bound = true;

    doc.addEventListener('click', async (event) => {
        const trigger = event.target.closest?.('[data-confirm], [data-confirm-message]');

        if (!trigger) {
            return;
        }

        if (trigger.matches('form')) {
            return;
        }

        const form = trigger.closest('form');
        const isSubmitControl = form && ['submit', ''].includes((trigger.getAttribute('type') || '').toLowerCase());

        if (isSubmitControl) {
            lastSubmitter = trigger;
            return;
        }

        const href = trigger.getAttribute('href');
        if (!href || href === '#') {
            return;
        }

        event.preventDefault();

        const result = await confirmAction(optionsFromElement(trigger));
        if (result?.confirmed || result === true) {
            getWindow().location.href = href;
        }
    }, true);

    doc.addEventListener('submit', async (event) => {
        const form = event.target;
        const submitter = event.submitter || lastSubmitter;
        const confirmSource = submitter?.matches?.('[data-confirm], [data-confirm-message]')
            ? submitter
            : form.matches?.('[data-confirm], [data-confirm-message]')
                ? form
                : null;

        lastSubmitter = null;

        if (!confirmSource || isConfirmedForm(form)) {
            unmarkFormConfirmed(form);
            return;
        }

        if (!form.reportValidity()) {
            return;
        }

        event.preventDefault();

        const result = await confirmAction(optionsFromElement(confirmSource));
        if (!(result?.confirmed || result === true)) {
            return;
        }

        markFormConfirmed(form);

        if (typeof form.requestSubmit === 'function' && submitter?.type === 'submit') {
            form.requestSubmit(submitter);
            return;
        }

        form.submit();
    }, true);
}

export function installActionConfirmation() {
    const win = getWindow();
    const doc = getDocument();

    if (!win) {
        return;
    }

    win.confirmAction = confirmAction;
    win.openConfirmModal = confirmAction;
    win.closeConfirmModal = closeConfirmModal;
    win.AppUI = Object.assign(win.AppUI || {}, {
        confirmAction,
        openConfirmModal: confirmAction,
        closeConfirmModal,
    });

    if (!doc) {
        return;
    }

    if (doc.readyState === 'loading') {
        doc.addEventListener('DOMContentLoaded', bindDeclarativeConfirmations, { once: true });
    } else {
        bindDeclarativeConfirmations();
    }
}
