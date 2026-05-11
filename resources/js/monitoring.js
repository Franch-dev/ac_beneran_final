/**
 * Monitoring Page JavaScript
 * Handles service order management, invoicing, and workflow actions
 */

// === ORDER DETAIL MODAL ===
window.showOrderDetail = async function(orderId, orderNumber, masjidName, serviceDate) {
    const serviceOrderId = Number(orderId);
    if (!Number.isInteger(serviceOrderId) || serviceOrderId <= 0) {
        showToast('Service order tidak valid', 'error');
        return;
    }

    orderNumber = String(orderNumber ?? '-');
    masjidName = String(masjidName ?? '-');
    serviceDate = String(serviceDate ?? '-');

    const modal = document.getElementById('orderDetailPopup');
    const body = document.getElementById('orderDetailBody');

    if (!modal || !body) {
        showToast('Modal tidak ditemukan', 'error');
        return;
    }

    body.innerHTML = '<div class="timeline-loading" style="text-align:center;padding:2rem;"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary)"></i><p style="margin-top:1rem;">Memuat detail...</p></div>';
    modal.classList.add('active');

    try {
        // Track current order for history loading
        window.__currentOrderId = serviceOrderId;
        const order = await apiFetch(`/service-order/${serviceOrderId}`, 'GET');
        const orderData = order.data || order.order || order;
        const safeText = window.escapeHtml || ((val) => String(val ?? ''));

        // Validate and sanitize order data before processing
        if (!orderData) {
            throw new Error('Data order tidak valid');
        }

        const statusLabel = String(orderData.status || '').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());

        // Process service details with comprehensive error handling
        let detailsHtml = '<p class="text-muted">Tidak ada detail</p>';
        try {
            if (orderData.service_details && Array.isArray(orderData.service_details) && orderData.service_details.length > 0) {
                detailsHtml = orderData.service_details.map(d => {
                    try {
                        // Sanitize service_type to remove any image references, HTML tags, or problematic content
                        const sanitizedServiceType = safeText(d.service_type || '')
                            .replace(/<[^>]*>/g, '') // Remove HTML tags
                            .replace(/https?:\/\/[^\\s]*\.(png|jpg|jpeg|gif|webp|svg)/gi, '') // Remove image URLs
                            .replace(/data:image\/[^;]+;base64,[A-Za-z0-9+\/=]+/g, '') // Remove data URIs
                            .trim();

                        return `
                            <div style="display:flex;justify-content:space-between;padding:0.5rem;background:var(--bg);border-radius:4px;margin-bottom:0.5rem;">
                                <span>${safeText(d.pk_type)} ${safeText(d.brand || '')} × ${d.quantity || 0}</span>
                                <span>${sanitizedServiceType}</span>
                            </div>
                        `;
                    } catch (error) {
                        // Fallback for individual service detail processing errors
                        console.warn('Error processing service detail:', error);
                        return `
                            <div style="display:flex;justify-content:space-between;padding:0.5rem;background:var(--bg);border-radius:4px;margin-bottom:0.5rem;">
                                <span>${safeText(d.pk_type)} ${safeText(d.brand || '')} × ${d.quantity || 0}</span>
                                <span>${safeText(d.service_type || '')}</span>
                            </div>
                        `;
                    }
                }).join('');
            }
        } catch (detailsError) {
            console.warn('Error processing service details:', detailsError);
            detailsHtml = '<p class="text-muted">Tidak ada detail</p>';
        }

        // After loading, fetch and render timeline
        const timelineData = await apiFetch(`/workflow/${serviceOrderId}/timeline`, 'GET');
        let timelineHtml = '<div style="margin-top:1rem;"><div style="font-weight:600;margin-bottom:0.5rem;color:var(--text);"><i class="fas fa-stream"></i> Timeline Workflow</div>';
        
        if (timelineData.steps && timelineData.steps.length > 0) {
            timelineHtml += timelineData.steps.map(step => `
                <div style="padding-left:1rem; border-left:2px solid var(--primary); margin-bottom:0.5rem; font-size:0.85rem;">
                    <strong>${safeText(step.label)}</strong> — <small>${safeText(step.time)}</small><br>
                    <small>${safeText(step.actor_name)} (${safeText(step.actor_role)})</small>
                    ${step.notes ? `<div style="font-size:0.75rem; color:var(--text-muted);">${safeText(step.notes)}</div>` : ''}
                </div>
            `).join('');
        } else {
            timelineHtml += '<p class="text-muted text-sm">Belum ada aktivitas.</p>';
        }
        timelineHtml += '</div>';

        // After loading, fetch and render history (if any)
        window.loadOrderHistory?.(serviceOrderId);

        // Build the modal content with error handling
        let modalContent = '';
        try {
            const spkRoute = `/service-order/${serviceOrderId}/spk`;
            const invoiceRoute = `/service-order/${serviceOrderId}/invoice`;

            modalContent = `
                <div class="order-detail-popup">
                    <div class="od-row" style="margin-bottom:0.5rem; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                        <span class="od-label"><i class="fas fa-clipboard-list"></i> No. Order:</span>
                        <strong style="word-break:break-all;">${safeText(orderNumber)}</strong>
                    </div>
                    <div class="od-row" style="margin-bottom:0.5rem; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                        <span class="od-label"><i class="fas fa-mosque"></i> Masjid:</span>
                        <strong style="word-break:break-word; text-align:right;">${safeText(masjidName)}</strong>
                    </div>
                    <div class="od-row" style="margin-bottom:0.5rem; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                        <span class="od-label"><i class="fas fa-calendar"></i> Tanggal Servis:</span>
                        <strong>${safeText(serviceDate)}</strong>
                    </div>
                    <div class="od-row" style="margin-bottom:0.5rem; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                        <span class="od-label"><i class="fas fa-info-circle"></i> Status:</span>
                        <span class="status-badge status-${safeText(orderData.status || '')}">${safeText(statusLabel)}</span>
                    </div>
                    
                    ${(orderData.status !== 'spk_invoice_created' && orderData.invoice) ? `
                    <div style="margin-top:1rem; display:flex; gap:0.5rem;">
                        <a href="${spkRoute}" target="_blank" class="btn btn-sm btn-secondary">
                            <i class="fas fa-file-alt"></i> SPK
                        </a>
                        <a href="${invoiceRoute}" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-file-invoice"></i> Invoice
                        </a>
                    </div>
                    ` : ''}

                    <div style="margin-top:1rem;">
                        <div style="font-weight:600;margin-bottom:0.5rem;color:var(--text);">
                            <i class="fas fa-list"></i> Detail Unit AC
                        </div>
                        ${detailsHtml}
                    </div>
                    ${timelineHtml}
                    ${orderData.notes ? `
                    <div style="margin-top:1rem;padding:0.75rem;background:var(--info-soft);border-radius:4px;border:1px solid var(--info);font-size:0.85rem;color:var(--info);">
                        <i class="fas fa-sticky-note"></i> <strong>Catatan:</strong> ${safeText(orderData.notes)}
                    </div>
                    ` : ''}
                </div>
            `;

        } catch (contentError) {
            console.error('Error building modal content:', contentError);
            modalContent = `
                <div style="padding:1rem;background:var(--danger-soft);color:var(--danger);border-radius:4px;font-size:0.9rem;">
                    <i class="fas fa-exclamation-triangle"></i> Gagal memuat tampilan detail: ${contentError.message}
                </div>
            `;
        }

        body.innerHTML = modalContent;
    } catch (err) {
        body.innerHTML = `
            <div style="padding:1rem;background:var(--danger-soft);color:var(--danger);border-radius:4px;font-size:0.9rem;">
                <i class="fas fa-exclamation-triangle"></i> Gagal memuat detail: ${err.message}
            </div>
        `;
    }
};

// Load history for a given order and render in the order detail modal
window.loadOrderHistory = async function(orderId) {
    const oid = Number(orderId);
    if (!Number.isInteger(oid) || oid <= 0) return;
    try {
        const resp = await apiFetch(`/modules/ac-masjid-musholla/service-order/${oid}/history`, 'GET');
        const histories = resp?.histories ?? [];
        const listEl = document.getElementById('orderHistoryList');
        if (!listEl) return;
        if (!histories.length) {
            listEl.innerHTML = '<div class="text-muted" style="padding:0.5rem 0;">Tidak ada riwayat.</div>';
            return;
        }
        listEl.innerHTML = histories.map(h => {
            const when = h.archived_at ? new Date(h.archived_at).toLocaleString() : '';
            const snapshot = h.order_snapshot || {};
            const details = Object.entries(snapshot).map(([k,v]) => `<div><strong>${k}</strong>: ${String(v)}</div>`).join('');
            return `<div class="history-item" style="padding:0.4rem 0;border-bottom:1px solid var(--border);">
                        <div class="history-meta" style="font-size:0.8rem;color:var(--muted);">${when}</div>
                        ${h.summary ? `<div class="history-summary" style="font-weight:600;margin-top:0.25rem;">${h.summary}</div>` : ''}
                        <div class="history-snapshot" style="font-family: ui-monospace, SFMono-Regular, Menlo, Consolas; font-size:0.75rem; margin-top:0.25rem;">
                            ${details}
                        </div>
                    </div>`;
        }).join('');
    } catch (err) {
        console.error(err);
        showToast('Riwayat gagal dimuat: ' + (err?.message ?? 'Error'), 'warning');
    }
};

// Helper: ensure we have a global csrf token for fetch calls
// === CREATE SPK & INVOICE ===
window.createSpkInvoice = async function(id) {
    openConfirmModal({
        type: 'success',
        heading: 'Buat SPK & Invoice?',
        message: 'Order akan diproses, SPK dan Invoice akan dibuat sekaligus.',
        confirmText: 'Ya, Buat',
        onConfirm: async () => {
            try {
                showToast('Membuat SPK & Invoice...', 'info');
                const url = (typeof ROUTES_MON !== 'undefined' && ROUTES_MON.workflowCreateSpkInvoice)
                    ? ROUTES_MON.workflowCreateSpkInvoice(id)
                    : `/service-order/${id}/create-spk-invoice`;
                await apiFetch(url, 'POST');
                showToast('SPK & Invoice berhasil dibuat.', 'success');
                refreshMonitoringSurface?.();
            } catch (err) {
                showToast('Gagal membuat SPK & Invoice: ' + (err.message || 'Error'), 'error');
            }
        }
    });
};

// === HANDLE COMPLETED ORDER ===
window.handleCompletedOrder = function(id) {
    openConfirmModal({
        type: 'success',
        heading: 'Kelola Order Selesai',
        message: 'Apa yang ingin Anda lakukan dengan order ini?',
        confirmText: 'Lihat Detail',
        onConfirm: async () => {
            try {
                const response = await apiFetch(`/service-order/${id}`, 'GET');
                const order = response.data || response;

                // Show order completion details
                showOrderDetail(id, order.order_number, order.masjid?.name, order.service_date);
            } catch (err) {
                showToast('Error: ' + (err.message || 'Terjadi kesalahan'), 'error');
            }
        }
    });
};

// === GENERATE SPK & INVOICE ===
window.generateInvoice = function(id) {
    return createSpkInvoice(id);
};

// === APPROVE INVOICE ===
window.approveInvoice = async function(id) {
    openConfirmModal({
        type: 'success',
        heading: 'Approve SPK & Invoice?',
        message: 'Invoice akan disetujui dan order akan diselesaikan.',
        confirmText: 'Ya, Setuju',
        onConfirm: async () => {
            try {
                const url = (typeof ROUTES_MON !== 'undefined' && ROUTES_MON.workflowApproveInvoice)
                    ? ROUTES_MON.workflowApproveInvoice(id)
                    : `/service-order/${id}/approve-invoice`;
                await apiFetch(url, 'POST');
                showToast('SPK & Invoice berhasil disetujui.', 'success');
                refreshMonitoringSurface?.();
            } catch (err) {
                showToast('Gagal menyetujui SPK & Invoice: ' + (err.message || 'Error'), 'error');
            }
        }
    });
};

window.approveSpkInvoice = window.approveInvoice;

// === DELETE SERVICE ORDER ===
window.deleteServiceOrder = function(id, e) {
    if (e) e.preventDefault();

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

// Wrapper: Archive order from UI (per plan)
window.archiveOrder = async function(orderId) {
    try {
        const url = (typeof ROUTES_MON !== 'undefined' && ROUTES_MON.serviceOrderArchive)
            ? ROUTES_MON.serviceOrderArchive(orderId)
            : `/modules/ac-masjid-musholla/service-order/${orderId}/archive`;
        await apiFetch(url, 'POST');
        showToast('Order di-archive ke Riwayat.', 'success');
        refreshMonitoringSurface?.();
    } catch (err) {
        showToast('Gagal meng-archive: ' + (err.message || 'Error'), 'error');
    }
};

// Wrapper: Delete order (hard delete) via existing route
window.deleteOrder = function(orderId) {
    deleteServiceOrder(orderId);
};

// Wrapper: Approve SPK & Invoice using existing flow
window.approveSpkInvoice = async function(orderId) {
    return window.approveInvoice(orderId);
};

// === MODAL CONFIRMATION HELPERS ===
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

// === TOAST NOTIFICATIONS ===
function showToast(message, type = 'info') {
    // Check if global showToast exists
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
        return;
    }

    // Fallback toast implementation
    let toast = document.getElementById('custom-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'custom-toast';
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s;
        `;
        document.body.appendChild(toast);
    }

    const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };

    toast.style.backgroundColor = colors[type] || colors.info;
    toast.textContent = message;
    toast.style.opacity = '1';

    setTimeout(() => {
        toast.style.opacity = '0';
    }, 3000);
}

// === API FETCH HELPER ===
async function apiFetch(url, method = 'GET', body = null) {
    const options = {
        method: method,
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    };

    if (body && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
        options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);

    if (!response.ok) {
        const error = await response.json().catch(() => ({}));
        throw new Error(error.message || `HTTP ${response.status}`);
    }

    return response.json();
}

// === REFRESH MONITORING ===
function refreshMonitoringSurface() {
    if (typeof window.refreshMonitoringData === 'function') {
        window.refreshMonitoringData();
    } else {
        // Fallback: reload page
        window.location.reload();
    }
}

// Make refreshMonitoringSurface available globally
window.refreshMonitoringSurface = refreshMonitoringSurface;

// === OPEN ASSIGN TECHNICIAN POPUP ===
window.openAssignTech = async function(orderId, orderNumber, masjidName) {
    const serviceOrderId = Number(orderId);
    if (!Number.isInteger(serviceOrderId) || serviceOrderId <= 0) {
        showToast('Service order tidak valid', 'error');
        return;
    }

    const orderIdField = document.getElementById('assignTechOrderId');
    const orderInfo = document.getElementById('assignTechOrderInfo');
    const technicianSelect = document.getElementById('technicianSelect');
    const notesField = document.getElementById('assignTechNotes');

    if (!orderIdField || !orderInfo || !technicianSelect || !notesField) {
        showToast('Form penugasan tidak ditemukan', 'error');
        return;
    }

    orderIdField.value = serviceOrderId;
    orderInfo.textContent = `Order: ${String(orderNumber ?? '-')} - ${String(masjidName ?? '-')}`;
    notesField.value = '';
    technicianSelect.innerHTML = '<option value="">Memuat daftar teknisi...</option>';

    openPopup('assignTechPopup');

    try {
        const techniciansUrl = (window.ROUTES_MON && window.ROUTES_MON.workflowTechnicians)
            ? window.ROUTES_MON.workflowTechnicians
            : `${window.ROUTES_MON?.workflowBase ?? '/workflow'}/technicians`;
        const techniciansResponse = await apiFetch(techniciansUrl, 'GET');
        console.log('DEBUG: Technicians API response:', techniciansResponse);
        const technicians = Array.isArray(techniciansResponse)
            ? techniciansResponse
            : Array.isArray(techniciansResponse.data)
                ? techniciansResponse.data
                : [];
        console.log('DEBUG: Final technicians array:', technicians);

        if (technicians.length > 0) {
            technicianSelect.innerHTML = '<option value="">- Pilih Teknisi -</option>' + technicians.map(t => {
                const label = [t.name, t.email].filter(Boolean).join(' - ');
                return `<option value="${t.id}">${label}</option>`;
            }).join('');
            console.log('DEBUG: Technician select innerHTML updated.');
        } else {
            console.log('DEBUG: Technicians array empty.');
            technicianSelect.innerHTML = '<option value="">Tidak ada teknisi terdaftar</option>';
        }
    } catch (err) {
        technicianSelect.innerHTML = '<option value="">Gagal memuat teknisi</option>';
        console.error('Failed loading technician list:', err);
        showToast('Gagal memuat daftar teknisi: ' + (err.message || 'Error'), 'warning');
    }
};

window.submitAssignTech = async function() {
    const orderId = document.getElementById('assignTechOrderId').value;
    const technicianId = document.getElementById('technicianSelect').value;
    const notes = document.getElementById('assignTechNotes').value;

    if (!technicianId) {
        showToast('Pilih teknisi terlebih dahulu.', 'warning');
        return;
    }
    try {
        showToast('Menugaskan teknisi...', 'info');
        const assignUrl = (window.ROUTES_MON && window.ROUTES_MON.workflowBase)
            ? `${window.ROUTES_MON.workflowBase}/${orderId}/assign`
            : `/workflow/${orderId}/assign`;
        await apiFetch(assignUrl, 'POST', {
            technician_id: technicianId,
            notes: notes,
        });
        showToast('Teknisi berhasil ditugaskan!', 'success');
        closePopup('assignTechPopup');
        refreshMonitoringSurface?.();
    } catch (err) {
        showToast('Gagal menugaskan: ' + (err.message || 'Error'), 'error');
    }
};

// === SHOW ORDER HISTORY ===
window.showOrderHistory = async function() {
    const selectedItem = document.querySelector('.masjid-select-item.selected');
    if (!selectedItem) {
        showToast('Silakan pilih masjid terlebih dahulu', 'warning');
        return;
    }
    const masjidId = selectedItem.dataset.id;
    
    // Create or get container
    let container = document.getElementById('soHistoryContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'soHistoryContainer';
        container.style.cssText = 'margin-top:1rem; padding-top:1rem; border-top:1px solid var(--border);';
        document.getElementById('soFormContent').appendChild(container);
    }
    
    container.innerHTML = '<div style="text-align:center;padding:1rem;"><i class="fas fa-spinner fa-spin"></i> Memuat history...</div>';

    try {
        const history = await apiFetch(`/masjid/${masjidId}/history-json`, 'GET');
        
        if (history.length === 0) {
            container.innerHTML = '<p class="text-muted text-sm">Tidak ada riwayat untuk masjid ini.</p>';
            return;
        }

        container.innerHTML = '<h5 style="margin-bottom:0.5rem;"><i class="fas fa-history"></i> Riwayat Order</h5>' + history.map(o => `
            <div class="history-item" style="border:1px solid var(--border); border-radius:4px; margin-bottom:0.5rem; padding:0.5rem;">
                <div style="cursor:pointer; display:flex; justify-content:space-between;" onclick="this.nextElementSibling.classList.toggle('active')">
                    <strong>${o.order_number}</strong>
                    <span class="status-badge status-${o.status}">${o.status.replaceAll('_', ' ')}</span>
                </div>
                <div class="history-details" style="display:none; padding-top:0.5rem; border-top:1px dashed var(--border); margin-top:0.5rem;">
                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.5rem;">
                        <span><strong>Tanggal:</strong> ${o.service_date}</span>
                        <span><strong>Total:</strong> Rp ${o.total_price.toLocaleString('id-ID')}</span>
                    </div>
                    <div style="margin-bottom:0.5rem;">
                        <strong>Unit AC:</strong>
                        ${o.details.map(d => `<div style="font-size:0.75rem; padding-left:0.5rem;">• ${d.pk_type} ${d.brand} - <em>${d.service_type}</em> ${d.complaint ? `<br><small style="color:var(--text-muted)">Keluhan: ${d.complaint}</small>` : ''}</div>`).join('')}
                    </div>
                    <div>
                        <strong>Riwayat Workflow:</strong>
                        ${o.steps.map(s => `<div style="font-size:0.75rem; padding-left:0.5rem;">• ${s.time} - <strong>${s.step.replaceAll('_', ' ')}</strong> ${s.notes ? `: <small>${s.notes}</small>` : ''}</div>`).join('')}
                    </div>
                </div>
            </div>
        `).join('');
        
        // Add minimal CSS for active class
        if (!document.getElementById('history-style')) {
            const style = document.createElement('style');
            style.id = 'history-style';
            style.textContent = '.history-details.active { display:block !important; }';
            document.head.appendChild(style);
        }
    } catch (err) {
        container.innerHTML = `<p class="text-danger text-sm">Gagal memuat: ${err.message}</p>`;
    }
};

// === SERVICE ORDER POPUP: PK-SELECTOR ARCHITECTURE ===
// Tracks active detail groups: { groupId, pkType, quantity, serviceType, complaint }
// Each group = one row group in the form
let soActiveGroups = []; // { id, pkType, qty, serviceType, complaint }
let soGroupCounter = 0;

// On masjid click: parse acUnits, render PK badges, show form
window.selectMasjidForSO = function(element) {
    const masjidId = element.dataset.id;
    const masjidName = element.dataset.name;
    const masjidAddress = element.dataset.address;
    const phoneNumbers = JSON.parse(element.dataset.phone || '[]');
    const acUnits = JSON.parse(element.dataset.ac || '[]');
    const masjidType = element.dataset.type || 'masjid';

    document.getElementById('soMasjidName').textContent = masjidName;
    document.getElementById('soMasjidAddress').textContent = masjidAddress;
    document.getElementById('soPhone').value = phoneNumbers[0] || '';

    // Store for pricing lookup
    window.__soMasjidType = masjidType;
    window.__soAcUnits = acUnits;

    // Group acUnits by pk_type to show counts
    const pkCounts = {};
    acUnits.forEach(u => {
        pkCounts[u.pk_type] = (pkCounts[u.pk_type] || 0) + (u.quantity || 0);
    });
    const pkTotal = acUnits.reduce((sum, u) => sum + (u.quantity || 0), 0);
    const pkTypes = Object.keys(pkCounts).filter(pk => pkCounts[pk] > 0);

    // Render PK badges (only pk_types that exist, with available qty)
    const badgesEl = document.getElementById('soPkBadges');
    badgesEl.innerHTML = pkTypes.map(pk => {
        const qty = pkCounts[pk] || 0;
        // Pick the first unit of this pk_type to get the canonical brand
        const firstUnit = acUnits.find(u => u.pk_type === pk);
        const brand = firstUnit ? firstUnit.brand : 'General';
        return `<button type="button" class="pk-badge"
            onclick="addPKRowGroup('${pk}', ${qty}, '${brand}')">
            <span class="pk-badge__type">${pk}</span>
            <span class="pk-badge__qty">&times;${qty}</span>
        </button>`;
    }).join('');

    // Summary line
    document.getElementById('soAcSummary').innerHTML =
        `<i class="fas fa-box"></i> Total <strong>${pkTotal}</strong> unit | ` +
        `<strong>${pkTypes.length}</strong> tipe (` +
        pkTypes.join(', ') + `)`;

    // Reset groups
    soActiveGroups = [];
    soGroupCounter = 0;
    renderDetailGroups();

    document.getElementById('soFormContent').style.display = 'block';
};

// Add a PK row group (cumulative — user can add multiple PK types)
window.addPKRowGroup = function(pkType, maxQty, brand) {
    // Guard: no units available for this PK type
    if (!maxQty || maxQty <= 0) {
        showToast(`Tidak ada unit ${pkType} tersedia.`, 'warning');
        return;
    }
    // Prevent duplicate PK type rows; if already present, prompt user to adjust quantity instead
    const existing = soActiveGroups.find(g => g.pkType === pkType);
    if (existing) {
        showToast(`PK ${pkType} sudah ditambahkan, ubah jumlahnya saja.`, 'warning');
        return;
    }
    const id = ++soGroupCounter;
    soActiveGroups.push({
        id,
        pkType,
        qty: 1,
        maxQty: maxQty,   // available units for this PK type
        brand: brand,     // brand from the first ac unit of this pk_type
        serviceType: 'cleaning',
        complaint: '',
    });
    renderDetailGroups();
    updatePricingPreview();
};

// Render all PK row groups into the form
function renderDetailGroups() {
    const container = document.getElementById('soDetailGroups');
    if (!container) return;

    container.innerHTML = soActiveGroups.map(group => {
        return `
<div class="pk-row-group" data-group-id="${group.id}" id="pkGroup${group.id}">
    <div class="pk-row-group__header">
        <span class="pk-row-group__title">
            <i class="fas fa-cube"></i> <strong>${group.pkType}</strong>
        </span>
        <button type="button" class="btn btn-sm btn-danger" onclick="removePKRowGroup(${group.id})">
            <i class="fas fa-trash"></i>
        </button>
    </div>
    <div class="pk-row-group__body">
        <div class="form-row">
            <div class="form-group" style="flex:1">
                <label class="form-label">Jumlah Unit</label>
                <input type="number" class="form-input"
                    id="soGroupQty${group.id}"
                    value="${group.qty}"
                    min="1" max="${group.maxQty}"
                    onchange="updateGroupQty(${group.id}, this.value)">
            </div>
            <div class="form-group" style="flex:2">
                <label class="form-label">Jenis Servis</label>
                <select class="form-select" id="soGroupSvc${group.id}"
                    onchange="updateGroupSvc(${group.id}, this.value)">
                    <option value="cleaning" ${group.serviceType === 'cleaning' ? 'selected' : ''}>Cuci Biasa</option>
                    <option value="deepcleaning" ${group.serviceType === 'deepcleaning' ? 'selected' : ''}>Cuci Deep</option>
                    <option value="service" ${group.serviceType === 'service' ? 'selected' : ''}>Service Ringan</option>
                    <option value="overhaul" ${group.serviceType === 'overhaul' ? 'selected' : ''}>Overhaul</option>
                    <option value="gas" ${group.serviceType === 'gas' ? 'selected' : ''}>Isi Ulang Gas</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Keluhan</label>
            <textarea class="form-textarea"
                id="soGroupComplaint${group.id}"
                rows="2"
                placeholder="Masukan keluhan..."
                oninput="updateGroupComplaint(${group.id}, this.value)">${group.complaint}</textarea>
        </div>
    </div>
</div>`;
    }).join('');

    if (soActiveGroups.length === 0) {
        container.innerHTML = `
<div class="so-empty-groups">
    <i class="fas fa-hand-pointer"></i>
    <p>Klik tombol PK di atas untuk menambahkan unit</p>
</div>`;
    }
}

window.updateGroupQty = function(groupId, value) {
    const group = soActiveGroups.find(g => g.id === groupId);
    if (group) {
        const maxAllowed = group.maxQty || 100;
        group.qty = Math.max(1, Math.min(maxAllowed, parseInt(value) || 1));
        document.getElementById('soGroupQty' + groupId).value = group.qty;
        updatePricingPreview();
    }
};

window.updateGroupSvc = function(groupId, value) {
    const group = soActiveGroups.find(g => g.id === groupId);
    if (group) {
        group.serviceType = value;
    }
};

window.updateGroupComplaint = function(groupId, value) {
    const group = soActiveGroups.find(g => g.id === groupId);
    if (group) {
        group.complaint = value;
    }
};

window.removePKRowGroup = function(groupId) {
    soActiveGroups = soActiveGroups.filter(g => g.id !== groupId);
    renderDetailGroups();
    updatePricingPreview();
};

// Live pricing calculation
function updatePricingPreview() {
    const itemsEl = document.getElementById('soPriceItems');
    const totalEl = document.getElementById('soTotalPreview');
    if (!itemsEl || !totalEl) return;

    const hargaMap = window.HARGA_CONFIG?.MASJID || { '1PK': 150000, '2PK': 200000, '5PK': 350000 };
    const mushollaMap = window.HARGA_CONFIG?.MUSHOLLA || { '1PK': 120000, '2PK': 170000, '5PK': 300000 };
    const type = window.__soMasjidType || 'masjid';
    const priceMap = (type === 'musholla') ? mushollaMap : hargaMap;

    let total = 0;
    let itemsHtml = '';
    soActiveGroups.forEach(group => {
        const price = priceMap[group.pkType] || 0;
        const subtotal = price * group.qty;
        total += subtotal;
        itemsHtml += `
<div class="so-price-item">
    <span>${group.pkType} &times; ${group.qty} unit</span>
    <span>Rp ${subtotal.toLocaleString('id-ID')}</span>
</div>`;
    });

    itemsEl.innerHTML = itemsHtml;
    totalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
}

// Submit service order
window.submitServiceOrder = async function() {
    const masjidName = document.getElementById('soMasjidName').textContent;
    if (!masjidName) {
        showToast('Silakan pilih masjid terlebih dahulu', 'warning');
        return;
    }
    if (soActiveGroups.length === 0) {
        showToast('Tambahkan minimal 1 unit AC', 'warning');
        return;
    }

    const meetingPerson = document.getElementById('soMeetingPerson').value;
    const phone = document.getElementById('soPhone').value;
    const serviceDate = document.getElementById('soServiceDate').value;
    const notes = document.getElementById('soNotes').value;
    const selectedItem = document.querySelector('.masjid-select-item.selected');
    const masjidId = selectedItem ? selectedItem.dataset.id : null;

    if (!masjidId) {
        showToast('Silakan pilih masjid', 'warning');
        return;
    }

    const acDetails = soActiveGroups.map(group => ({
        pk_type: group.pkType,
        brand: group.brand || 'General',
        quantity: group.qty,
        service_type: group.serviceType,
        complaint: group.complaint || '-',
    }));

    try {
        showToast('Membuat service order...', 'info');
        const response = await apiFetch('/service-order', 'POST', {
            masjid_id: masjidId,
            meeting_person: meetingPerson,
            phone,
            service_date: serviceDate,
            notes: notes || null,
            details: acDetails,
        });
        showToast('Service order berhasil dibuat!', 'success');
        closePopup('serviceOrderPopup');
        resetServiceOrderForm();
        refreshMonitoringSurface?.();
    } catch (err) {
        showToast('Gagal membuat service order: ' + (err.message || 'Error'), 'error');
    }
};

window.resetServiceOrderForm = function() {
    document.getElementById('soMasjidName').textContent = '';
    document.getElementById('soMasjidAddress').textContent = '';
    document.getElementById('soPhone').value = '';
    document.getElementById('soServiceDate').value = '';
    document.getElementById('soNotes').value = '';
    soActiveGroups = [];
    soGroupCounter = 0;
    document.getElementById('soFormContent').style.display = 'none';
    document.querySelectorAll('.masjid-select-item').forEach(i => i.classList.remove('selected'));
};

window.addSODetail = function() {
    const detailsList = document.getElementById('soDetailsList');
    const index = detailsList.children.length;

    const detailDiv = document.createElement('div');
    detailDiv.className = 'so-detail-item';
    detailDiv.innerHTML = `
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Unit ${index + 1}</label>
                <select class="form-select so-ac-type" data-index="${index}">
                    <option value="split">Split AC</option>
                    <option value="window">Window AC</option>
                    <option value="central">Central AC</option>
                    <option value="casette">Cassette AC</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Jenis Servis</label>
                <select class="form-select so-service-type" data-index="${index}">
                    <option value="cleaning">Cuci Biasa</option>
                    <option value="deepcleaning">Cuci Deep</option>
                    <option value="service">Service Ringan</option>
                    <option value="overhaul">Overhaul</option>
                    <option value="gas">Isi Ulang Gas</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Keluhan</label>
                <textarea class="form-input so-complaint" data-index="${index}" rows="2" placeholder="Masukan keluhan..."></textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <button type="button" class="btn btn-sm btn-outline remove-detail" onclick="removeSODetail(this)">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </div>
        </div>
    `;
    detailsList.appendChild(detailDiv);
};

window.removeSODetail = function(button) {
    const detailItem = button.closest('.so-detail-item');
    if (detailItem) {
        detailItem.remove();
        // Re-index remaining items
        const detailsList = document.getElementById('soDetailsList');
        detailsList.querySelectorAll('.so-detail-item').forEach((item, idx) => {
            item.querySelectorAll('[data-index]').forEach(el => {
                el.dataset.index = idx;
                const label = el.closest('.form-group').querySelector('.form-label');
                if (label) {
                    const num = idx + 1;
                    if (el.classList.contains('so-ac-type')) {
                        label.textContent = `Unit ${num}`;
                    } else if (el.classList.contains('so-service-type')) {
                        label.textContent = `Jenis Servis`;
                    } else if (el.classList.contains('so-complaint')) {
                        label.textContent = `Keluhan`;
                    }
                }
            });
        });
    }
};

window.submitServiceOrderDuplicate = async function() {
    try {
        showToast('Membuat service order...', 'info');

        const masjidName = document.getElementById('soMasjidName').textContent;
        if (!masjidName || masjidName === '') {
            showToast('Silakan pilih masjid terlebih dahulu', 'warning');
            return;
        }

        const meetingPerson = document.getElementById('soMeetingPerson').value;
        const phone = document.getElementById('soPhone').value;
        const serviceDate = document.getElementById('soServiceDate').value;

        const detailsList = document.getElementById('soDetailsList');
        const detailItems = detailsList.querySelectorAll('.so-detail-item');

        const acDetails = [];
        detailItems.forEach((item, index) => {
            const acType = item.querySelector('.so-ac-type').value;
            const serviceType = item.querySelector('.so-service-type').value;
            const complaint = item.querySelector('.so-complaint').value;

            acDetails.push({
                ac_type: acType,
                service_type: serviceType,
                complaint: complaint || '-'
            });
        });

        // Find masjid ID from selected item
        const selectedItem = document.querySelector('.masjid-select-item.selected');
        const masjidId = selectedItem ? selectedItem.dataset.id : null;

        if (!masjidId) {
            showToast('Silakan pilih masjid', 'warning');
            return;
        }

        const orderData = {
            masjid_id: masjidId,
            meeting_person: meetingPerson,
            phone: phone,
            service_date: serviceDate,
            ac_details: acDetails
        };

        await apiFetch('/service-order', 'POST', orderData);

        showToast('Service order berhasil dibuat!', 'success');
        closePopup('serviceOrderPopup');
        refreshMonitoringSurface?.();
    } catch (err) {
        showToast('Gagal membuat order: ' + (err.message || 'Error'), 'error');
    }
};

// ============================================
// REPLACE ORDER FLOW
// ============================================
window.confirmReplaceOrder = function() {
    showToast('Konfirmasi Replace Order', 'info');
};

window.cancelReplaceOrder = function() {
    showToast('Replace Order dibatalkan.', 'info');
};

// ============================================
// CUSTOM CONFIRM MODAL
// ============================================
window.closeConfirmModal = function() {
    document.getElementById('confirmModal')?.classList.remove('active');
};

window.executeConfirmAction = function() {
    showToast('Action executed.', 'success');
    closeConfirmModal();
};

// ============================================
// MANUAL REFRESH
// ============================================
window.manualRefreshMonitoring = function() {
    showToast('Memuat ulang...', 'info');
    refreshMonitoringSurface?.();
};

// ============================================
// MARK TASK DONE
// ============================================
window.markTaskDone = async function(orderId) {
    openConfirmModal({
        type: 'success',
        heading: 'Tandai Selesai?',
        message: 'Order akan ditandai sebagai selesai dan menunggu approval pembayaran.',
        confirmText: 'Ya, Selesai',
        onConfirm: async () => {
            try {
                // Call the workflow progress endpoint with status='done'
                const url = (typeof ROUTES_MON !== 'undefined' && ROUTES_MON.workflowBase)
                    ? `${ROUTES_MON.workflowBase}/${orderId}/progress`
                    : `/workflow/${orderId}/progress`;

                await apiFetch(url, 'POST', {
                    status: 'done',
                    notes: 'Pekerjaan selesai',
                });

                showToast('Order ditandai selesai!', 'success');
                refreshMonitoringSurface?.();
            } catch (err) {
                showToast('Gagal: ' + (err.message || 'Error'), 'error');
            }
        }
    });
};

// Fallback for openPopup/closePopup (in case ui/runtime not loaded yet)
if (typeof window.openPopup === 'undefined') {
    window.openPopup = function(id) {
        document.getElementById(id)?.classList.add('active');
    };
}
if (typeof window.closePopup === 'undefined') {
    window.closePopup = function(id) {
        if (id) {
            document.getElementById(id)?.classList.remove('active');
        }
    };
}

// ============================================
// SHOW MASJID SIDE DETAIL
// ============================================
window.showMasjidSideDetail = async function(masjidId) {
    const safeMasjidId = Number(masjidId);
    if (!Number.isInteger(safeMasjidId) || safeMasjidId <= 0) {
        showToast("Masjid tidak valid.", "warning");
        return;
    }

    try {
        showToast("Memuat detail masjid...", "info");
        const response = await apiFetch(`/masjid/${safeMasjidId}`, "GET");
        showToast("Detail masjid dimuat.", "success");
    } catch (err) {
        showToast("Gagal memuat: " + (err.message || "Error"), "error");
    }
};

// ============================================
// SHOW WORKFLOW TIMELINE
// ============================================
window.showWorkflowTimeline = async function(orderId, orderNumber, masjidName) {
    const serviceOrderId = Number(orderId);
    if (!Number.isInteger(serviceOrderId) || serviceOrderId <= 0) {
        showToast('Service order tidak valid', 'error');
        return;
    }

    orderNumber = String(orderNumber ?? '-');
    masjidName = String(masjidName ?? '-');

    const modal = document.getElementById('workflowTimelineModal');
    const body = document.getElementById('workflowTimelineBody');

    if (!modal || !body) {
        showToast('Modal tidak ditemukan', 'error');
        return;
    }

    body.innerHTML = '<div class="timeline-loading" style="text-align:center;padding:2rem;"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary)"></i><p style="margin-top:1rem;">Memuat timeline...</p></div>';
    modal.classList.add('active');

    try {
        const data = await apiFetch(`/workflow/${serviceOrderId}/timeline`, 'GET');
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
                <!-- Riwayat (History) panel -->
                <div class="order-history-panel" style="margin-top:1rem;border-top:1px dashed var(--border);padding-top:1rem;">
                    <div style="font-weight:600;margin-bottom:0.5rem;color:var(--text)"><i class="fas fa-history"></i> Riwayat</div>
                    <div id="orderHistoryList" class="order-history-list"></div>
                </div>
            </div>
        `;

        body.innerHTML = combinedHtml;
    } catch (err) {
        body.innerHTML = `
            <div style="padding:1rem;background:var(--danger-soft);color:var(--danger);border-radius:4px;font-size:0.9rem;">
                <i class="fas fa-exclamation-triangle"></i> Gagal memuat timeline: ${err.message}
            </div>
        `;
    }
};

// Initialize masjid selection
document.addEventListener('DOMContentLoaded', function() {
    const masjidItems = document.querySelectorAll('.masjid-select-item');
    masjidItems.forEach(item => {
        item.addEventListener('click', function() {
            // Remove selected class from all
            document.querySelectorAll('.masjid-select-item').forEach(i => i.classList.remove('selected'));
            // Add to clicked
            this.classList.add('selected');
            selectMasjidForSO(this);
        });
    });
});

// ============================================
// FIELD REPORT (Technician)
// ============================================
window.openFieldReport = function(orderId, orderNumber) {
    document.getElementById('fieldReportOrderId').value = orderId;
    document.getElementById('fieldReportNotes').value = '';
    document.getElementById('fieldReportAdditionalFee').value = '0';
    document.getElementById('toolsMaterialsList').innerHTML = '';
    addToolMaterialRow();
    openPopup('fieldReportPopup');
};

window.addToolMaterialRow = function() {
    const container = document.getElementById('toolsMaterialsList');
    const row = document.createElement('div');
    row.className = 'tools-material-row';
    row.style.cssText = 'display:flex;gap:8px;margin-bottom:8px;';
    row.innerHTML = `
        <input type="text" name="tm_name[]" class="form-input" placeholder="Nama alat/bahan" style="flex:2">
        <input type="number" name="tm_quantity[]" class="form-input" placeholder="Qty" min="1" value="1" style="flex:1">
        <input type="number" name="tm_price[]" class="form-input" placeholder="Harga" style="flex:1">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.tools-material-row').remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(row);
};

document.getElementById('fieldReportForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const orderId = document.getElementById('fieldReportOrderId').value;
    const notes = document.getElementById('fieldReportNotes').value;
    const additionalFee = parseFloat(document.getElementById('fieldReportAdditionalFee').value) || 0;

    const toolsMaterials = [];
    document.querySelectorAll('.tools-material-row').forEach(row => {
        const name = row.querySelector('[name="tm_name[]"]').value;
        const qty = parseInt(row.querySelector('[name="tm_quantity[]"]').value) || 1;
        const price = parseFloat(row.querySelector('[name="tm_price[]"]').value) || 0;
        if (name && name.trim()) {
            toolsMaterials.push({ name, quantity: qty, price });
        }
    });

    try {
        showToast('Mengirim laporan pekerjaan...', 'info');

        const response = await apiFetch(`/service-order/${orderId}/field-report`, 'POST', {
            field_report_notes: notes,
            field_report_additional_fee: additionalFee,
            field_report_tools_materials: toolsMaterials.length > 0 ? toolsMaterials : null
        });

        showToast('Laporan berhasil dikirim!', 'success');
        closePopup('fieldReportPopup');
        refreshMonitoringSurface?.();
    } catch (err) {
        showToast('Gagal mengirim laporan: ' + (err.message || 'Error'), 'error');
    }
});

// ============================================
// ADDITIONAL FEE APPROVAL (Manager)
// ============================================
window.approveAdditionalFee = function(orderId) {
    document.getElementById('additionalFeeInfo').innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <span>Teknisi mengajukan biaya tambahan. Apakah Anda menyetujui?</span>
    `;
    document.getElementById('approveAdditionalFeeOrderId')?.remove();
    const input = document.createElement('input');
    input.type = 'hidden';
    input.id = 'approveAdditionalFeeOrderId';
    input.value = orderId;
    document.getElementById('additionalFeeApprovalPopup').appendChild(input);
    openPopup('additionalFeeApprovalPopup');
};

window.confirmAdditionalFee = async function() {
    const orderId = document.getElementById('approveAdditionalFeeOrderId')?.value;
    if (!orderId) return;

    try {
        showToast('Menyetuju biaya tambahan...', 'info');

        await apiFetch(`/service-order/${orderId}/approve-additional-fee`, 'POST');

        showToast('Biaya tambahan disetujui!', 'success');
        closePopup('additionalFeeApprovalPopup');
        refreshMonitoringSurface?.();
    } catch (err) {
        showToast('Gagal menyetujui: ' + (err.message || 'Error'), 'error');
    }
};

// ============================================
// DUAL CONFIRMATION (Order Selesai)
// ============================================
let pendingDualConfirm = { orderId: null, role: null };

window.openDualConfirmation = function(orderId, role, message) {
    pendingDualConfirm = { orderId, role };
    document.getElementById('dualConfirmMessage').innerHTML = message || 'Konfirmasi bahwa service order telah selesai?';
    openPopup('dualConfirmPopup');
};

window.submitDualConfirmation = async function() {
    const { orderId, role } = pendingDualConfirm;
    if (!orderId || !role) return;

    try {
        showToast('Mengonfirmasi...', 'info');

        const endpoint = role === 'frontdesk'
            ? `/service-order/${orderId}/frontdesk-confirm-complete`
            : `/service-order/${orderId}/manager-confirm-complete`;

        await apiFetch(endpoint, 'POST');

        showToast('Konfirmasi berhasil!', 'success');
        closePopup('dualConfirmPopup');
        refreshMonitoringSurface?.();
    } catch (err) {
        showToast('Gagal mengonfirmasi: ' + (err.message || 'Error'), 'error');
    }
};
