import './bootstrap';
import './animations.js';
import './navigation.js';
import { registerUiRuntime } from './ui/runtime.js';

registerUiRuntime();

// === GLOBAL CONFIRM MODAL ===
function createModalOverlay() {
    const id = 'confirm-modal-overlay';
    let overlay = document.getElementById(id);
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = id;
        overlay.className = 'modal-overlay';
        overlay.innerHTML = `
            <div class="modal-container">
                <div class="modal-header">
                    <h3 class="modal-heading"></h3>
                    <button type="button" class="modal-close" aria-label="Tutup">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="modal-message"></p>
                    <div class="modal-order-context" style="display:none;margin-top:12px;padding:12px;background:var(--gray-50);border-radius:var(--radius);font-size:0.85rem;">
                        <div style="display:flex;justify-content:space-between;padding:3px 0;"><span style="color:var(--text-muted)">No. Order</span><strong class="modal-ctx-order"></strong></div>
                        <div style="display:flex;justify-content:space-between;padding:3px 0;"><span style="color:var(--text-muted)">Masjid</span><strong class="modal-ctx-masjid"></strong></div>
                        <div style="display:flex;justify-content:space-between;padding:3px 0;"><span style="color:var(--text-muted)">Tanggal</span><strong class="modal-ctx-date"></strong></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-cancel">Batal</button>
                    <button type="button" class="btn modal-confirm"></button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        overlay.querySelector('.modal-close').addEventListener('click', () => window.closeConfirmModal());
        overlay.querySelector('.modal-cancel').addEventListener('click', () => window.closeConfirmModal());
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) window.closeConfirmModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') window.closeConfirmModal();
        });
    }
    return overlay;
}

window.closeConfirmModal = function() {
    const overlay = document.getElementById('confirm-modal-overlay');
    if (overlay) overlay.classList.remove('active');
};

window.openConfirmModal = function(options) {
    const { type = 'danger', heading, message, confirmText, onConfirm, orderData } = options;
    const overlay = createModalOverlay();
    const container = overlay.querySelector('.modal-container');

    container.className = 'modal-container modal-' + type;
    overlay.querySelector('.modal-heading').textContent = heading || 'Konfirmasi';

    const ctxEl = overlay.querySelector('.modal-order-context');
    if (orderData) {
        overlay.querySelector('.modal-ctx-order').textContent = orderData.orderNumber || '-';
        overlay.querySelector('.modal-ctx-masjid').textContent = orderData.masjidName || '-';
        overlay.querySelector('.modal-ctx-date').textContent = orderData.serviceDate || '-';
        ctxEl.style.display = 'block';
    } else {
        ctxEl.style.display = 'none';
    }

    overlay.querySelector('.modal-message').innerHTML = message || '';

    const confirmBtn = overlay.querySelector('.modal-confirm');
    confirmBtn.textContent = confirmText || 'Ya, Lanjutkan';
    confirmBtn.className = 'btn modal-confirm btn-' + type;

    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

    newConfirmBtn.addEventListener('click', () => {
        window.closeConfirmModal();
        if (typeof onConfirm === 'function') onConfirm();
    });

    overlay.classList.add('active');
};

