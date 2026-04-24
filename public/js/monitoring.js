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
    openConfirmModal({
        type: 'danger',
        heading: 'Hapus Order?',
        message: 'Order akan dihapus secara permanen.',
        confirmText: 'Ya, Hapus',
        onConfirm: async () => {
            try {
                const url = (typeof ROUTES_MON !== 'undefined' && ROUTES_MON.soDelete)
                    ? ROUTES_MON.soDelete(id)
                    : `/service-order/${id}`;
                await apiFetch(url, 'DELETE');
                showToast('Order berhasil dihapus.', 'success');
                refreshMonitoringSurface?.();
            } catch (err) {
                showToast('Error: ' + (err.message || 'Terjadi kesalahan'), 'error');
            }
        }
    });
};


window.showOrderDetail = async function(orderId, orderNumber, masjidName, serviceDate) {
    // Tampilkan state loading sementara di modal konfirmasi standar
    openConfirmModal({
        type: 'info',
        heading: 'Detail & Riwayat Workflow',
        message: '<div style="text-align:center;padding:2rem;"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary)"></i><p style="margin-top:1rem;">Memuat detail order...</p></div>',
        confirmText: 'Tutup',
        onConfirm: () => {}
    });

    try {
        const data = await apiFetch(`/workflow/${orderId}/timeline`);
        const safeText = window.escapeHtml || ((val) => String(val ?? ''));
        
        let stepsHtml = '';
        if (data.steps && data.steps.length > 0) {
            stepsHtml = data.steps.map(step => `
                <div class="timeline-item">
                    <div class="timeline-icon" style="background:${step.color}">
                        <i class="${step.icon}"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-label">${safeText(step.label)}</div>
                        <div class="timeline-actor">${safeText(step.actor_name)} <span class="role-badge">${safeText(step.actor_role)}</span></div>
                        ${step.notes ? `<div class="timeline-notes">${safeText(step.notes)}</div>` : ''}
                        <div class="timeline-time">${safeText(step.time)}</div>
                    </div>
                </div>
            `).join('');
        }

        let assignmentHtml = '';
        if (data.assignment) {
            const statusLabel = String(data.assignment.status || '').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
            assignmentHtml = `
                <div class="assignment-section" style="margin-top:1.5rem; border-top:1px dashed var(--border); padding-top:1rem;">
                    <div style="font-weight:600;margin-bottom:0.75rem;color:var(--primary);">
                        <i class="fas fa-user-hard-hat"></i> Teknisi Ditugaskan
                    </div>
                    <div style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem;background:var(--primary-soft);border-radius:var(--radius);margin-bottom:1rem">
                        <div style="font-weight:600">${safeText(data.assignment.technician_name)}</div>
                        <span class="status-badge status-${data.assignment.status}">${safeText(statusLabel)}</span>
                    </div>
                    ${data.assignment.notes ? `<div class="timeline-notes" style="margin-bottom:0.5rem">${safeText(data.assignment.notes)}</div>` : ''}
                    ${data.assignment.started_at ? `<div class="timeline-time" style="font-size:0.8rem">Mulai: ${safeText(data.assignment.started_at)}</div>` : ''}
                    ${data.assignment.completed_at ? `<div class="timeline-time" style="font-size:0.8rem">Selesai: ${safeText(data.assignment.completed_at)}</div>` : ''}
                </div>
            `;
        }

        const combinedHtml = `
            <div class="order-detail-popup">
                <div class="od-row" style="margin-bottom:0.5rem; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                    <span class="od-label"><i class="fas fa-clipboard-list"></i> No. Order:</span>
                    <strong style="word-break:break-all;">${safeText(orderNumber)}</strong>
                </div>
                <div class="od-row" style="margin-bottom:0.5rem; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                    <span class="od-label"><i class="fas fa-mosque"></i> Masjid:</span>
                    <strong style="word-break:break-word; text-align:right;">${safeText(masjidName)}</strong>
                </div>
                <div class="od-row" style="margin-bottom:1.5rem; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                    <span class="od-label"><i class="fas fa-calendar"></i> Tanggal Servis:</span>
                    <strong>${safeText(serviceDate)}</strong>
                </div>

                <div class="timeline-container-wrapper" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:1rem; display:flex; flex-direction:column;">
                    <div style="font-weight:600;margin-bottom:1rem;color:var(--text);border-bottom:1px solid var(--border);padding-bottom:0.5rem;flex-shrink:0;">
                        <i class="fas fa-stream"></i> Riwayat Workflow
                    </div>
                    <div class="timeline-scroll-area" style="overflow-y:auto; max-height:40vh; padding-right:0.5rem; padding-bottom:0.5rem;">
                        ${stepsHtml}
                        ${assignmentHtml}
                        ${(!data.steps || !data.steps.length) && !data.assignment ? '<div class="empty-state" style="padding:1rem 0;min-height:auto;"><i class="fas fa-stream"></i><p>Belum ada aktivitas workflow</p></div>' : ''}
                    </div>
                </div>
            </div>
        `;

        const overlay = document.getElementById('confirm-modal-overlay');
        if (overlay) {
            const container = overlay.querySelector('.modal-container');
            container.className = 'modal-container modal-info'; // ensure size/color
            // Inject new HTML
            overlay.querySelector('.modal-message').innerHTML = combinedHtml;
            // Reposition button
            const confirmBtn = overlay.querySelector('.modal-confirm');
            confirmBtn.textContent = 'Tutup';
        }
    } catch (err) {
        // Fallback jika gagal fetch timeline
        const overlay = document.getElementById('confirm-modal-overlay');
        if (overlay) {
            overlay.querySelector('.modal-message').innerHTML = `
                <div class="order-detail-popup">
                    <div class="od-row" style="margin-bottom:0.5rem; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                        <span class="od-label"><i class="fas fa-clipboard-list"></i> No. Order:</span>
                        <strong style="word-break:break-all;">${window.escapeHtml ? window.escapeHtml(orderNumber) : orderNumber}</strong>
                    </div>
                    <div class="od-row" style="margin-bottom:0.5rem; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                        <span class="od-label"><i class="fas fa-mosque"></i> Masjid:</span>
                        <strong style="word-break:break-word; text-align:right;">${window.escapeHtml ? window.escapeHtml(masjidName) : masjidName}</strong>
                    </div>
                    <div class="od-row" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                        <span class="od-label"><i class="fas fa-calendar"></i> Tanggal Servis:</span>
                        <strong>${window.escapeHtml ? window.escapeHtml(serviceDate) : serviceDate}</strong>
                    </div>
                    <div style="margin-top:1rem;padding:0.75rem;background:var(--danger-soft);color:var(--danger);border-radius:4px;font-size:0.85rem;">
                        <i class="fas fa-exclamation-triangle"></i> Gagal memuat timeline: ${err.message}
                    </div>
                </div>
            `;
        }
    }
};


window.handleCompletedOrder = function(id) {
    openConfirmModal({
        type: 'danger',
        heading: 'Hapus Order Selesai?',
        message: 'Order selesai akan dihapus dari monitoring table.',
        confirmText: 'Ya, Hapus',
        onConfirm: async () => {
            try {
                await apiFetch('/service-orders/close', 'POST', {
                    service_order_ids: [id]
                });
                showToast('Order selesai dihapus dari tabel.', 'success');
                if (typeof refreshMonitoringSurface === 'function') refreshMonitoringSurface();
            } catch (err) {
                showToast('Error: ' + (err.message || 'Terjadi kesalahan'), 'error');
            }
        }
    });
};

window.approveOrder = function(id) {
    openConfirmModal({
        type: 'success',
        heading: 'Approve Service Order?',
        message: 'Order ini akan disetujui dan SPK akan diterbitkan.',
        confirmText: 'Ya, Approve',
        onConfirm: async () => {
            try {
                const url = ROUTES_MON?.soApprove ? ROUTES_MON.soApprove(id) : `/service-order/${id}/approve`;
                await apiFetch(url, 'POST');
                showToast('Order disetujui, SPK diterbitkan.', 'success');
                if (typeof refreshMonitoringSurface === 'function') refreshMonitoringSurface();
            } catch (err) {
                showToast('Error: ' + (err.message || 'Gagal approve'), 'error');
            }
        }
    });
};

window.generateInvoice = function(id) {
    openConfirmModal({
        type: 'primary',
        heading: 'Buat SPK & Invoice?',
        message: 'Akan membuat SPK & invoice untuk order ini. Pastikan detail sudah benar.',
        confirmText: 'Buat SPK & Invoice',
        onConfirm: async () => {
            try {
                const url = ROUTES_MON?.invoice ? ROUTES_MON.invoice(id) : `/service-order/${id}/invoice`;
                await apiFetch(url, 'POST');
                showToast('SPK & Invoice berhasil dibuat.', 'success');
                if (typeof refreshMonitoringSurface === 'function') refreshMonitoringSurface();
            } catch (err) {
                showToast('Error: ' + (err.message || 'Gagal membuat invoice'), 'error');
            }
        }
    });
};

window.approveInvoice = function(id) {
    openConfirmModal({
        type: 'success',
        heading: 'Approve Invoice?',
        message: 'Invoice akan disetujui dan status order menjadi Completed.',
        confirmText: 'Approve Invoice',
        onConfirm: async () => {
            try {
                await apiFetch(`/service-order/${id}/approve-invoice`, 'POST');
                showToast('Invoice disetujui.', 'success');
                if (typeof refreshMonitoringSurface === 'function') refreshMonitoringSurface();
            } catch (err) {
                showToast('Error: ' + (err.message || 'Gagal menyetujui invoice'), 'error');
            }
        }
    });
};

window.markTaskDone = function(id) {
    openConfirmModal({
        type: 'warning',
        heading: 'Tandai Selesai?',
        message: 'Apakah Anda yakin tugas teknisi sudah selesai?',
        confirmText: 'Ya, Selesai',
        onConfirm: async () => {
            try {
                await apiFetch(`/workflow/${id}/progress`, 'POST', { status: 'done' });
                showToast('Tugas ditandai selesai.', 'success');
                if (typeof refreshMonitoringSurface === 'function') refreshMonitoringSurface();
            } catch (err) {
                showToast('Error: ' + (err.message || 'Gagal update tugas'), 'error');
            }
        }
    });
};

window.showMasjidSideDetail = async function(masjidId) {
    let panel = document.getElementById('masjid-side-panel');
    if (!panel) {
        panel = document.createElement('div');
        panel.id = 'masjid-side-panel';
        panel.className = 'side-panel';
        panel.innerHTML = `
            <div class="side-panel-header" style="padding:1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;font-size:1.25rem;"><i class="fas fa-mosque"></i> Detail Masjid</h3>
                <button class="popup-close" onclick="closeMasjidSidePanel()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text);">&times;</button>
            </div>
            <div class="side-panel-body" id="masjid-side-body" style="padding:1.5rem;overflow-y:auto;flex-grow:1;">
                <div style="text-align:center;padding:2rem;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
        `;
        
        let overlay = document.createElement('div');
        overlay.id = 'side-panel-overlay';
        overlay.className = 'side-panel-overlay';
        overlay.onclick = window.closeMasjidSidePanel;
        
        document.body.appendChild(overlay);
        document.body.appendChild(panel);

        const style = document.createElement('style');
        style.innerHTML = `
            .side-panel {
                position: fixed; top: 0; right: -400px; width: 100%; max-width: 400px; height: 100vh;
                background: var(--surface); box-shadow: -5px 0 25px rgba(0,0,0,0.1);
                z-index: 10000; transition: right 0.3s ease; display: flex; flex-direction: column;
            }
            .side-panel.active { right: 0; }
            .side-panel-overlay {
                position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
                background: rgba(0,0,0,0.4); z-index: 9999; opacity: 0; visibility: hidden; transition: all 0.3s ease;
            }
            .side-panel-overlay.active { opacity: 1; visibility: visible; }
        `;
        document.head.appendChild(style);
    }

    document.getElementById('masjid-side-body').innerHTML = '<div style="text-align:center;padding:2rem;"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary)"></i></div>';
    document.getElementById('masjid-side-panel').classList.add('active');
    document.getElementById('side-panel-overlay').classList.add('active');

    try {
        const url = (typeof ROUTES_MON !== 'undefined' && ROUTES_MON.masjidDetail) 
                    ? ROUTES_MON.masjidDetail(masjidId) 
                    : \`/modules/ac-masjid-musholla/masjid/\${masjidId}\`;
        const data = await apiFetch(url);
        
        const safeText = window.escapeHtml || ((val) => String(val ?? ''));
        
        let acHtml = '<div style="margin-top:1.5rem;padding-top:1rem;border-top:1px dashed var(--border)"><h4 style="margin-bottom:1rem;color:var(--text);">Daftar Unit AC</h4>';
        if (data.ac_units && data.ac_units.length > 0) {
            acHtml += data.ac_units.map(unit => `
                <div style="background:var(--bg);padding:0.75rem;border-radius:var(--radius);margin-bottom:0.5rem;border:1px solid var(--border);">
                    <div style="font-weight:600;">${safeText(unit.pk_type)} ${safeText(unit.brand)}</div>
                    <div style="font-size:0.85rem;color:var(--text-muted);margin-top:0.25rem;">
                        <i class="fas fa-box"></i> ${unit.quantity} unit &nbsp;|&nbsp; <i class="fas fa-tools"></i> Servis: ${unit.last_service_date ? new Date(unit.last_service_date).toLocaleDateString('id-ID') : '-'}
                    </div>
                </div>
            `).join('');
        } else {
            acHtml += '<p class="text-muted" style="font-size:0.9rem;">Belum ada data AC yang terdaftar.</p>';
        }
        acHtml += '</div>';

        const phonesHtml = (data.phone_numbers || []).map(p => `<div style="margin-bottom:0.25rem;"><i class="fas fa-phone fa-fw text-muted"></i> ${safeText(p)}</div>`).join('');

        document.getElementById('masjid-side-body').innerHTML = `
            <div style="margin-bottom:1.5rem;">
                <h4 style="margin:0 0 0.5rem 0;font-size:1.2rem;color:var(--primary);">${safeText(data.name)}</h4>
                <span class="status-badge" style="background:var(--primary-soft);color:var(--primary);">${safeText(data.custom_id)}</span>
            </div>
            
            <div style="margin-bottom:1.5rem;font-size:0.95rem;">
                <div style="margin-bottom:0.5rem;color:var(--text);"><i class="fas fa-map-marker-alt fa-fw text-muted"></i> ${safeText(data.address)}</div>
                ${phonesHtml}
            </div>
            
            <div style="margin-bottom:1.5rem;font-size:0.95rem;background:var(--surface);padding:1rem;border-radius:var(--radius);border:1px solid var(--border);">
                <div style="margin-bottom:0.5rem;"><i class="fas fa-user fa-fw text-muted"></i> <strong>DKM:</strong> ${safeText(data.dkm_name || '-')}</div>
                <div><i class="fas fa-user-tag fa-fw text-muted"></i> <strong>Marbot:</strong> ${safeText(data.marbot_name || '-')}</div>
            </div>

            ${acHtml}
        `;
    } catch(err) {
        document.getElementById('masjid-side-body').innerHTML = `
            <div style="padding:1rem;background:var(--danger-soft);color:var(--danger);border-radius:4px;font-size:0.9rem;">
                <i class="fas fa-exclamation-circle"></i> Gagal memuat data masjid: ${err.message}
            </div>
        `;
    }
};

window.closeMasjidSidePanel = function() {
    document.getElementById('masjid-side-panel')?.classList.remove('active');
    document.getElementById('side-panel-overlay')?.classList.remove('active');
};
