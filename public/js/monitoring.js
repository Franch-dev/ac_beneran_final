/* Add to end of existing monitoring.js */

// === MODAL CONFIRMATION ===
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-cancel">Batal</button>
                    <button type="button" class="btn modal-confirm"></button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        // Close handlers
        overlay.querySelector('.modal-close').addEventListener('click', () => closeConfirmModal());
        overlay.querySelector('.modal-cancel').addEventListener('click', () => closeConfirmModal());
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeConfirmModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeConfirmModal();
        });
    }
    return overlay;
}

function closeConfirmModal() {
    const overlay = document.getElementById('confirm-modal-overlay');
    if (overlay) overlay.classList.remove('active');
}

window.openConfirmModal = function(options) {
    const { type = 'danger', heading, message, confirmText, onConfirm, orderData } = options;
    const overlay = createModalOverlay();
    const container = overlay.querySelector('.modal-container');

    // Style based on type
    container.className = 'modal-container modal-' + type;
    overlay.querySelector('.modal-heading').textContent = heading || 'Konfirmasi';
    overlay.querySelector('.modal-message').innerHTML = message || (orderData ? `
        <div class="order-data-summary">
            <p><strong>No. Order:</strong> ${orderData.orderNumber || '-'}</p>
            <p><strong>Lokasi:</strong> ${orderData.masjidName || '-'}</p>
            <p><strong>Tanggal:</strong> ${orderData.serviceDate || '-'}</p>
        </div>
    ` : '');

    const confirmBtn = overlay.querySelector('.modal-confirm');
    confirmBtn.textContent = confirmText || 'Ya, Lanjutkan';
    confirmBtn.className = 'btn modal-confirm btn-' + type;

    // Replace old handler
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

    newConfirmBtn.addEventListener('click', () => {
        closeConfirmModal();
        if (typeof onConfirm === 'function') onConfirm();
    });

    overlay.classList.add('active');
};


// === DELETE SERVICE ORDER ===
window.deleteServiceOrder = function(id, e) {
    const evt = e || window.event;
    const row = evt?.target?.closest?.('tr');
    let orderNum, MasjidName, serviceDate;

    if (row) {
        orderNum = row.cells?.[0]?.querySelector?.('.order-num')?.textContent || 'Unknown';
        MasjidName = row.cells?.[1]?.querySelector?.('.location-name')?.textContent || 'Unknown';
        serviceDate = row.cells?.[2]?.querySelector?.('strong')?.textContent || 'Unknown';
    } else {
        // Fallback: find row by data attribute
        const btn = document.querySelector(`button[onclick*="deleteServiceOrder(${id}"]`);
        const fallbackRow = btn?.closest('tr') || document.querySelector(`tr[data-order-id="${id}"]`);
        if (fallbackRow) {
            orderNum = fallbackRow.cells?.[0]?.querySelector?.('.order-num')?.textContent || 'Unknown';
            MasjidName = fallbackRow.cells?.[1]?.querySelector?.('.location-name')?.textContent || 'Unknown';
            serviceDate = fallbackRow.cells?.[2]?.querySelector?.('strong')?.textContent || 'Unknown';
        } else {
            orderNum = 'Unknown';
            MasjidName = 'Unknown';
            serviceDate = 'Unknown';
        }
    }

    openConfirmModal({
        type: 'danger',
        heading: 'Hapus Order?',
        message: 'Order akan dihapus secara permanen.',
        confirmText: 'Ya, Hapus',
        orderData: {
            orderNumber: orderNum,
            MasjidName: MasjidName,
            serviceDate: serviceDate
        },
        onConfirm: async () => {
            try {
                await apiFetch('/service-orders/' + id, 'DELETE');
                showToast('Order berhasil dihapus.', 'success');

                // Remove row with animation
                if (row) {
                    row.style.transition = 'opacity 0.3s, transform 0.3s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                    setTimeout(() => row.remove(), 300);
                }
                refreshMonitoringSurface?.();
            } catch (err) {
                showToast('Error: ' + (err.message || 'Terjadi kesalahan'), 'error');
            }
        }
    });
};


window.showOrderDetail = function(orderId, orderNumber, masjidName, serviceDate) {
    openConfirmModal({
        type: 'info',
        heading: 'Detail Service Order',
        message: `
            <div class="order-detail-popup">
                <div class="od-row">
                    <span class="od-label"><i class="fas fa-clipboard-list"></i> No. Order:</span>
                    <strong>${orderNumber}</strong>
                </div>
                <div class="od-row">
                    <span class="od-label"><i class="fas fa-mosque"></i> Masjid:</span>
                    <strong>${masjidName}</strong>
                </div>
                <div class="od-row">
                    <span class="od-label"><i class="fas fa-calendar"></i> Tanggal Servis:</span>
                    <strong>${serviceDate}</strong>
                </div>
            </div>
        `,
        confirmText: 'Tutup',
        onConfirm: () => {},
        orderData: {
            orderNumber,
            masjidName,
            serviceDate
        }
    });
};


window.handleCompletedOrder = function(id, e) {
    const evt = e || window.event;
    const row = evt?.target?.closest?.('tr');
    if (!row) return;

    const orderNum = row.cells?.[0]?.querySelector?.('.order-num')?.textContent || 'Unknown';
    const MasjidName = row.cells?.[1]?.querySelector?.('.location-name')?.textContent || 'Unknown';
    const serviceDate = row.cells?.[2]?.querySelector?.('strong')?.textContent || 'Unknown';

    openConfirmModal({
        type: 'danger',
        heading: 'Hapus Order Selesai?',
        message: 'Order selesai akan dihapus dari monitoring table.',
        confirmText: 'Ya, Hapus',
        orderData: {
            orderNumber: orderNum,
            MasjidName: MasjidName,
            serviceDate: serviceDate
        },
        onConfirm: async () => {
            try {
                await apiFetch('/service-orders/close', 'POST', {
                    service_order_ids: [id]
                });
                showToast('Order selesai dihapus dari tabel.');
                refreshMonitoringSurface();
            } catch (err) {
                showToast('Error: ' + (err.message || 'Terjadi kesalahan'), 'error');
            }
        }
    });
};
