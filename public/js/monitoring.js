/* ==========================================
   MONITORING.JS — Service Orders Management
   ==========================================

   ╔══════════════════════════════════════════╗
   ║         PENGATURAN HARGA SERVIS          ║
   ║                                          ║
   ║  Ubah angka di bawah sesuai kebutuhan.   ║
   ║  Format: angka tanpa titik/koma          ║
   ║  Contoh: 150000 = Rp 150.000             ║
   ╚══════════════════════════════════════════╝
*/

const HARGA_SERVIS = {

    // ── Harga untuk MASJID ──────────────────
    masjid: {
        '1PK': 150000,   // Rp 150.000 per unit
        '2PK': 200000,   // Rp 200.000 per unit
        '5PK': 350000,   // Rp 350.000 per unit
    },

    // ── Harga untuk MUSHOLLA ────────────────
    musholla: {
        '1PK': 120000,   // Rp 120.000 per unit
        '2PK': 170000,   // Rp 170.000 per unit
        '5PK': 300000,   // Rp 300.000 per unit
    },

};

/* ==========================================
   JANGAN UBAH KODE DI BAWAH INI
   ========================================== */

let selectedMasjidData = null;
let soAcData = [];

const SERVICE_ORDER_STATUS_LABELS = {
    pending: 'Pending',
    approved: 'SPK Issued',
    in_progress: 'In Progress',
    waiting_invoice: 'Waiting Invoice',
    waiting_review: 'Waiting Review',
    completed: 'Completed',
};

const SERVICE_ORDER_STATUS_COLORS = {
    pending: 'var(--warning)',
    approved: 'var(--success)',
    in_progress: 'var(--primary)',
    waiting_invoice: 'var(--info)',
    waiting_review: '#9c27b0',
    completed: 'var(--success)',
};

// Simpan payload terakhir untuk dipakai saat user konfirmasi replace
let _lastPayload = null;
const monitoringSafeText = window.escapeHtml || ((value) => String(value ?? ''));
const safeCssColor = function (value) {
    const color = String(value ?? '').trim();
    return /^#[0-9a-fA-F]{3,8}$/.test(color) || /^rgba?\([^)]+\)$/.test(color) ? color : '#5f6368';
};

async function refreshMonitoringSurface() {
    if (typeof window.refreshCurrentPageSnapshot === 'function') {
        try {
            await window.refreshCurrentPageSnapshot();
            return;
        } catch (_error) {
            // Fall back only when snapshot refresh fails.
        }
    }

    window.location.reload();
}

async function manualRefreshMonitoring() {
    await refreshMonitoringSurface();
}

// Ambil harga berdasarkan tipe lokasi & PK
function getPriceByPK(pk) {
    const tipe = (selectedMasjidData && selectedMasjidData.type) ? selectedMasjidData.type : 'masjid';
    const hargaTipe = HARGA_SERVIS[tipe] || HARGA_SERVIS['masjid'];
    return hargaTipe[pk] || HARGA_SERVIS['masjid'][pk] || 150000;
}

// Format rupiah
function formatRupiah(angka) {
    return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
}

function labelForServiceOrderStatus(status) {
    return SERVICE_ORDER_STATUS_LABELS[status] || String(status || '')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, function (char) { return char.toUpperCase(); });
}

function colorForServiceOrderStatus(status) {
    return SERVICE_ORDER_STATUS_COLORS[status] || 'var(--text-primary)';
}

function formatServiceDate(value) {
    if (!value) {
        return '-';
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return parsed.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

// === MASJID SEARCH DI SO POPUP ===
document.getElementById('soMasjidSearch') && document.getElementById('soMasjidSearch').addEventListener('input', function () {
    const val = this.value.toLowerCase();
    document.querySelectorAll('.masjid-select-item').forEach(function(item) {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(val) ? '' : 'none';
    });
});

function selectMasjidForSO(el) {
    document.querySelectorAll('.masjid-select-item').forEach(function(i) { i.classList.remove('selected'); });
    el.classList.add('selected');

    const name    = el.getAttribute('data-name');
    const address = el.getAttribute('data-address');
    const dkm     = el.getAttribute('data-dkm');
    const marbot  = el.getAttribute('data-marbot');
    const type    = el.getAttribute('data-type') || 'masjid';
    const phones  = JSON.parse(el.getAttribute('data-phone') || '[]');
    soAcData      = JSON.parse(el.getAttribute('data-ac') || '[]');

    // Set selectedMasjidData DULU sebelum apapun
    selectedMasjidData = {
        id:      el.getAttribute('data-id'),
        name:    name,
        address: address,
        dkm:     dkm,
        marbot:  marbot,
        phones:  phones,
        type:    type,
    };

    document.getElementById('soMasjidName').textContent    = name;
    document.getElementById('soMasjidAddress').textContent = address;
    document.getElementById('soPhone').value               = phones[0] || '';

    const meetingPersonSelect = document.getElementById('soMeetingPerson');
    if (meetingPersonSelect && meetingPersonSelect.options.length >= 2) {
        meetingPersonSelect.options[0].textContent = 'DKM (' + dkm + ')';
        meetingPersonSelect.options[1].textContent = 'Marbot (' + marbot + ')';
    }

    // Tampilkan info harga sesuai tipe
    var hargaInfo = document.getElementById('soHargaInfo');
    if (hargaInfo) {
        var h = HARGA_SERVIS[type] || HARGA_SERVIS['masjid'];
        var tipeLabel = type === 'musholla' ? 'Musholla' : 'Masjid';
        hargaInfo.innerHTML =
            '<i class="fas fa-tag"></i> ' +
            'Harga <strong>' + tipeLabel + '</strong>: ' +
            '1PK = ' + formatRupiah(h['1PK']) + ' &nbsp;|&nbsp; ' +
            '2PK = ' + formatRupiah(h['2PK']) + ' &nbsp;|&nbsp; ' +
            '5PK = ' + formatRupiah(h['5PK']);
        hargaInfo.style.display = 'flex';
    }

    document.getElementById('soDetailsList').innerHTML = '';
    addSODetail();

    document.getElementById('soFormContent').style.display = 'block';
    document.getElementById('soEmptyState').style.display  = 'none';
}

// === BARIS DETAIL UNIT SERVIS ===
function addSODetail() {
    if (!selectedMasjidData) {
        showToast('Pilih masjid terlebih dahulu', 'error');
        return;
    }

    var container = document.getElementById('soDetailsList');
    var div = document.createElement('div');
    div.className = 'so-detail-row';

    var pkOptions = ['1PK', '2PK', '5PK']
        .map(function(pk) { return '<option value="' + pk + '">' + pk + '</option>'; })
        .join('');

    div.innerHTML =
        '<div class="form-group" style="margin:0">' +
            '<label class="form-label" style="font-size:0.75rem">PK</label>' +
            '<select class="form-select so-pk" onchange="onPKChange(this)">' +
                pkOptions +
            '</select>' +
        '</div>' +
        '<div class="form-group" style="margin:0">' +
            '<label class="form-label" style="font-size:0.75rem">Merk</label>' +
            '<select class="form-select so-brand" onchange="updateHargaPreview(this.closest(\'.so-detail-row\').querySelector(\'.so-pk\'))"></select>' +
        '</div>' +
        '<div class="form-group" style="margin:0">' +
            '<label class="form-label" style="font-size:0.75rem">Qty</label>' +
            '<input type="number" class="form-input so-qty" min="1" value="1" ' +
                'oninput="updateHargaPreview(this.closest(\'.so-detail-row\').querySelector(\'.so-pk\'))">' +
        '</div>' +
        '<button type="button" class="btn btn-sm btn-danger" ' +
            'onclick="this.parentElement.remove(); updateTotalPreview();" ' +
            'style="align-self:flex-end;margin-bottom:1rem">' +
            '<i class="fas fa-times"></i>' +
        '</button>' +
        '<div style="grid-column:1/-1;font-size:0.75rem;color:var(--text-muted);margin-top:-0.4rem;margin-bottom:0.25rem">' +
            '<span class="so-harga-preview"></span>' +
        '</div>';

    container.appendChild(div);
    updateBrandOptions(div.querySelector('.so-pk'));
    updateHargaPreview(div.querySelector('.so-pk'));
}

function onPKChange(pkSelect) {
    updateBrandOptions(pkSelect);
    updateHargaPreview(pkSelect);
}

function updateBrandOptions(pkSelect) {
    var pk = pkSelect.value;
    var row = pkSelect.closest('.so-detail-row');
    var brandSelect = row.querySelector('.so-brand');

    var available = soAcData.filter(function(u) { return u.pk_type === pk; });

    if (available.length > 0) {
        brandSelect.innerHTML = available.map(function(u) {
            return '<option value="' + u.brand + '" data-max="' + u.quantity + '">' +
                   u.brand + ' (max: ' + u.quantity + ')</option>';
        }).join('');
    } else {
        brandSelect.innerHTML = '<option value="">Tidak ada unit</option>';
    }
}

function updateHargaPreview(pkSelect) {
    if (!selectedMasjidData) return;

    var row   = pkSelect.closest('.so-detail-row');
    var pk    = row.querySelector('.so-pk').value;
    var qty   = parseInt(row.querySelector('.so-qty').value) || 1;
    var harga = getPriceByPK(pk);
    var sub   = harga * qty;

    var preview = row.querySelector('.so-harga-preview');
    if (preview) {
        preview.innerHTML =
            '<i class="fas fa-calculator" style="margin-right:0.3rem;color:var(--primary)"></i>' +
            formatRupiah(harga) + ' &times; ' + qty + ' unit = ' +
            '<strong style="color:var(--primary)">' + formatRupiah(sub) + '</strong>';
    }
    updateTotalPreview();
}

function updateTotalPreview() {
    var totalEl = document.getElementById('soTotalPreview');
    if (!totalEl) return;

    var total = 0;
    document.querySelectorAll('#soDetailsList .so-detail-row').forEach(function(row) {
        var pkEl  = row.querySelector('.so-pk');
        var qtyEl = row.querySelector('.so-qty');
        if (!pkEl || !qtyEl) return;
        var pk  = pkEl.value;
        var qty = parseInt(qtyEl.value) || 0;
        if (pk && qty > 0) total += getPriceByPK(pk) * qty;
    });

    totalEl.textContent = total > 0 ? formatRupiah(total) : 'Rp 0';
}

// === KUMPULKAN PAYLOAD DARI FORM ===
function buildPayload(forceReplace) {
    var rows = document.querySelectorAll('#soDetailsList .so-detail-row');
    var details = [];
    var valid = true;

    rows.forEach(function(row) {
        var pk     = row.querySelector('.so-pk').value;
        var brand  = row.querySelector('.so-brand').value;
        var qty    = parseInt(row.querySelector('.so-qty').value);
        var maxOpt = row.querySelector('.so-brand option:checked');
        var max    = maxOpt ? parseInt(maxOpt.getAttribute('data-max') || '9999') : 9999;

        if (!pk || !brand || !qty || qty < 1) { valid = false; return; }

        if (qty > max) {
            showToast('Jumlah ' + pk + ' ' + brand + ' melebihi unit tersedia (max: ' + max + ')', 'error');
            valid = false;
            return;
        }

        details.push({
            pk_type:        pk,
            brand:          brand,
            quantity:       qty,
            price_per_unit: getPriceByPK(pk),
        });
    });

    if (!valid) return null;

    return {
        masjid_id:      selectedMasjidData.id,
        meeting_person: document.getElementById('soMeetingPerson').value,
        phone:          document.getElementById('soPhone').value,
        service_date:   document.getElementById('soServiceDate').value,
        notes:          document.getElementById('soNotes').value,
        details:        details,
        force_replace:  forceReplace ? true : false,
    };
}

// === KIRIM SERVICE ORDER ===
async function submitServiceOrder() {
    if (!selectedMasjidData) {
        showToast('Pilih masjid terlebih dahulu', 'error');
        return;
    }

    var rows = document.querySelectorAll('#soDetailsList .so-detail-row');
    if (!rows.length) {
        showToast('Tambahkan minimal satu unit servis', 'error');
        return;
    }

    var serviceDate = document.getElementById('soServiceDate').value;
    if (!serviceDate) {
        showToast('Pilih tanggal rencana servis', 'error');
        return;
    }

    var payload = buildPayload(false);
    if (!payload) return;

    _lastPayload = payload;

    try {
        var res = await apiFetch(ROUTES_MON.soStore, 'POST', payload);
        closePopup('serviceOrderPopup');
        showToast('Service Order berhasil dibuat! Tekan tombol Refresh untuk memuat data terbaru.');
    } catch (err) {
        // Cek apakah ada order aktif (dari err.data atau err.responseData)
        var errData = err.data || err.responseData || null;
        if (errData && errData.has_existing) {
            showReplaceConfirm(errData);
        } else {
            showToast(err.message || 'Terjadi kesalahan', 'error');
        }
    }
}

// === TAMPILKAN POPUP KONFIRMASI REPLACE ===
function showReplaceConfirm(data) {
    var existing = data.existing_order;
    var statusLabel = existing.status_label || labelForServiceOrderStatus(existing.status);
    var statusColor = colorForServiceOrderStatus(existing.status);

    var popup = document.getElementById('replaceConfirmPopup');
    if (!popup) return;

    document.getElementById('rcOrderNumber').textContent  = existing.order_number;
    document.getElementById('rcStatus').textContent       = statusLabel;
    document.getElementById('rcStatus').style.color       = statusColor;
    document.getElementById('rcServiceDate').textContent  = existing.service_date;

    openPopup('replaceConfirmPopup');
}

// === USER PILIH "YA, GANTI" ===
async function confirmReplaceOrder() {
    closePopup('replaceConfirmPopup');

    if (!_lastPayload) {
        showToast('Data tidak ditemukan, silakan coba lagi', 'error');
        return;
    }

    _lastPayload.force_replace = true;

    try {
        await apiFetch(ROUTES_MON.soStore, 'POST', _lastPayload);
        closePopup('serviceOrderPopup');
        showToast('Order lama diganti, Service Order baru berhasil dibuat! Tekan tombol Refresh untuk memuat data terbaru.');
    } catch (err) {
        showToast(err.message || 'Terjadi kesalahan', 'error');
    }
}

// === USER PILIH "TIDAK" ===
function cancelReplaceOrder() {
    closePopup('replaceConfirmPopup');
    showToast('Pembuatan order dibatalkan. Order lama tetap ada.', 'info');
}

// === APPROVE ===
// Note: This function is overridden at the bottom of the file to use confirmation modal
async function approveOrder(id) {
    // Original implementation - confirmation modal handles the prompt
    try {
        await apiFetch(ROUTES_MON.soApprove(id), 'POST');
        showToast('Order berhasil diapprove! Tekan tombol Refresh untuk memuat data terbaru.');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// === BATALKAN APPROVE ===
// Note: This function is overridden at the bottom of the file to use confirmation modal
async function cancelApprove(id) {
    // Original implementation - confirmation modal handles the prompt
    try {
        await apiFetch(ROUTES_MON.soCancel(id), 'POST');
        showToast('Approve dibatalkan, status kembali ke Pending. Tekan tombol Refresh untuk memuat data terbaru.');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// === HAPUS ORDER ===
// Note: This function is overridden at the bottom of the file to use confirmation modal
async function deleteOrder(id) {
    // Original implementation - confirmation modal handles the prompt
    try {
        await apiFetch(ROUTES_MON.soDeleteMgr(id), 'DELETE');
        showToast('Service order dihapus. Tekan tombol Refresh untuk memuat data terbaru.');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// === RIWAYAT ORDER ===
async function showOrderHistory() {
    if (!selectedMasjidData) return;

    try {
        var orders = await apiFetch(ROUTES_MON.soHistory(selectedMasjidData.id));
        var safe = window.escapeHtml || function (value) { return String(value ?? ''); };

        if (!orders.length) {
            document.getElementById('historyBody').innerHTML =
                '<div class="empty-state">' +
                    '<div class="empty-icon"><i class="fas fa-history"></i></div>' +
                    '<p>Belum ada riwayat servis</p>' +
                '</div>';
        } else {
            var html =
                '<div class="table-container"><table class="data-table">' +
                '<thead><tr>' +
                    '<th>No. Order</th><th>Tanggal</th><th>Status</th><th>Detail</th>' +
                '</tr></thead><tbody>';

            orders.forEach(function(o) {
                var details = (o.service_details || [])
                    .map(function(d) {
                        return safe(d.pk_type) + ' ' + safe(d.brand) + ' x' + safe(d.quantity);
                    })
                    .join(', ');
                var tgl = new Date(o.service_date).toLocaleDateString('id-ID', {
                    day: '2-digit', month: 'short', year: 'numeric'
                });
                html +=
                    '<tr>' +
                        '<td><span class="order-num">' + safe(o.order_number) + '</span></td>' +
                        '<td>' + tgl + '</td>' +
                        '<td><span class="status-badge status-' + safe(o.status) + '">' + safe(labelForServiceOrderStatus(o.status)) + '</span></td>' +
                        '<td>' + (details || '\u2013') + '</td>' +
                    '</tr>';
            });

            html += '</tbody></table></div>';
            document.getElementById('historyBody').innerHTML = html;
        }

        openPopup('historyPopup');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

/* ==========================================
   ACCOUNT MANAGER CONFIRMATION MODAL
   ========================================== */

// Store pending action for confirmation modal
let _confirmCallback = null;
let _confirmOrderData = null;

/**
 * Open confirmation modal with custom configuration
 * @param {Object} config - Configuration object
 * @param {string} config.type - 'success', 'warning', 'danger'
 * @param {string} config.heading - Modal heading text
 * @param {string} config.message - Main message text
 * @param {string} config.confirmText - Confirm button text
 * @param {string} config.cancelText - Cancel button text
 * @param {Function} config.onConfirm - Callback when confirmed
 * @param {Object} config.orderData - Optional order data to display
 */
function openConfirmModal(config) {
    const modal = document.getElementById('confirmModal');
    if (!modal) return;

    // Set icon based on type
    const iconEl = document.getElementById('confirmModalIcon');
    const iconMap = {
        success: { class: 'success', icon: 'fa-check-circle' },
        warning: { class: 'warning', icon: 'fa-exclamation-triangle' },
        danger:  { class: 'danger',  icon: 'fa-exclamation-circle' }
    };
    const iconConfig = iconMap[config.type] || iconMap.success;
    iconEl.className = 'confirm-icon ' + iconConfig.class;
    iconEl.innerHTML = '<i class="fas ' + iconConfig.icon + '"></i>';

    // Set text content
    document.getElementById('confirmModalHeading').textContent = config.heading || 'Konfirmasi Aksi';
    document.getElementById('confirmModalMessage').textContent = config.message || 'Apakah Anda yakin?';

    // Set button text
    const confirmBtn = document.getElementById('confirmModalConfirmBtn');
    confirmBtn.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> ' + (config.confirmText || 'Ya, Lanjutkan');

    // Update button class based on type
    confirmBtn.className = 'btn btn-' + (config.type === 'success' ? 'success' : config.type === 'warning' ? 'warning' : 'danger');

    // Show/hide details section
    const detailsEl = document.getElementById('confirmModalDetails');
    if (config.orderData) {
        _confirmOrderData = config.orderData;
        document.getElementById('confirmDetailOrder').textContent = config.orderData.orderNumber || '-';
        document.getElementById('confirmDetailMasjid').textContent = config.orderData.masjidName || '-';
        document.getElementById('confirmDetailDate').textContent = config.orderData.serviceDate || '-';
        detailsEl.style.display = 'block';
    } else {
        detailsEl.style.display = 'none';
    }

    // Store callback
    _confirmCallback = config.onConfirm;

    openPopup('confirmModal');

    // Focus management for accessibility
    setTimeout(() => {
        confirmBtn.focus();
    }, 100);
}

/**
 * Close confirmation modal
 */
function closeConfirmModal() {
    closePopup('confirmModal');

    // Clear callback
    _confirmCallback = null;
    _confirmOrderData = null;
}

/**
 * Execute the confirmed action
 */
function executeConfirmAction() {
    if (typeof _confirmCallback === 'function') {
        _confirmCallback();
    }
    closeConfirmModal();
}

// Override existing manager functions to use confirmation modal

// Override approveOrder to use confirmation modal
const _originalApproveOrder = approveOrder;
approveOrder = function(id) {
    // Find order data from the table
    const rowBtn = document.querySelector('tr [onclick="approveOrder(' + id + ')"]');
    const row = rowBtn ? rowBtn.closest('tr') : null;
    const cells = row ? row.querySelectorAll('td') : [];

    // Helper to safely get text content
    const getText = function(cell, selector) {
        if (!cell) return '-';
        const el = cell.querySelector(selector);
        return el ? el.textContent : '-';
    };

    openConfirmModal({
        type: 'success',
        heading: 'Konfirmasi Approval',
        message: 'Anda akan menyetujui service order ini. Order akan diproses dan invoice akan dibuat.',
        confirmText: 'Ya, Setujui',
        cancelText: 'Batal',
        orderData: {
            orderNumber: getText(cells[0], '.order-num'),
            masjidName: getText(cells[1], '.location-name'),
            serviceDate: getText(cells[2], 'strong')
        },
        onConfirm: function() {
            // Execute original approve logic
            _executeApprove(id);
        }
    });
};


// Internal approve execution
async function _executeApprove(id) {
    try {
        await apiFetch(ROUTES_MON.soApprove(id), 'POST');
        showToast('Order berhasil diapprove! Tekan tombol Refresh untuk memuat data terbaru.');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// Override cancelApprove to use confirmation modal
const _originalCancelApprove = cancelApprove;
cancelApprove = function(id) {
    // Find order data from the table
    const rowBtn = document.querySelector('tr [onclick="cancelApprove(' + id + ')"]');
    const row = rowBtn ? rowBtn.closest('tr') : null;
    const cells = row ? row.querySelectorAll('td') : [];

    // Helper to safely get text content
    const getText = function(cell, selector) {
        if (!cell) return '-';
        const el = cell.querySelector(selector);
        return el ? el.textContent : '-';
    };

    openConfirmModal({
        type: 'warning',
        heading: 'Batalkan Approval?',
        message: 'Status order akan kembali ke Pending. SPK dan Invoice yang sudah dibuat akan tetap tersimpan.',
        confirmText: 'Ya, Batalkan',
        cancelText: 'Tidak',
        orderData: {
            orderNumber: getText(cells[0], '.order-num'),
            masjidName: getText(cells[1], '.location-name'),
            serviceDate: getText(cells[2], 'strong')
        },
        onConfirm: function() {
            // Execute original cancel logic
            _executeCancelApprove(id);
        }
    });
};


// Internal cancel approve execution
async function _executeCancelApprove(id) {
    try {
        await apiFetch(ROUTES_MON.soCancel(id), 'POST');
        showToast('Approve dibatalkan, status kembali ke Pending. Tekan tombol Refresh untuk memuat data terbaru.');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// Override deleteOrder to use confirmation modal
const _originalDeleteOrder = deleteOrder;
deleteOrder = function(id) {
    // Find order data from the table
    const rowBtn = document.querySelector('tr [onclick="deleteOrder(' + id + ')"]');
    const row = rowBtn ? rowBtn.closest('tr') : null;
    const cells = row ? row.querySelectorAll('td') : [];

    // Helper to safely get text content
    const getText = function(cell, selector) {
        if (!cell) return '-';
        const el = cell.querySelector(selector);
        return el ? el.textContent : '-';
    };

    openConfirmModal({
        type: 'danger',
        heading: 'Hapus Service Order?',
        message: 'Tindakan ini tidak dapat dibatalkan. Semua data order, SPK, dan Invoice akan dihapus secara permanen.',
        confirmText: 'Ya, Hapus Permanen',
        cancelText: 'Batal',
        orderData: {
            orderNumber: getText(cells[0], '.order-num'),
            masjidName: getText(cells[1], '.location-name'),
            serviceDate: getText(cells[2], 'strong')
        },
        onConfirm: function() {
            // Execute original delete logic
            _executeDelete(id);
        }
    });
};


// Internal delete execution
async function _executeDelete(id) {
    try {
        await apiFetch(ROUTES_MON.soDeleteMgr(id), 'DELETE');
        showToast('Service order dihapus. Tekan tombol Refresh untuk memuat data terbaru.');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// Keyboard accessibility for confirmation modal
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('confirmModal');
    if (!modal || !modal.classList.contains('active')) return;

    if (e.key === 'Escape') {
        closeConfirmModal();
    }
});

// === Workflow helpers ===
async function markTaskDone(orderId) {
    try {
        await apiFetch(`/workflow/${orderId}/progress`, 'POST', { status: 'done' });
        showToast('Tugas ditandai selesai. Tekan tombol Refresh untuk memuat data terbaru.');
    } catch (err) {
        showToast(err.message || 'Gagal menandai selesai', 'error');
    }
}

async function generateInvoice(orderId) {
    try {
        await apiFetch(`/service-order/${orderId}/invoice`, 'POST');
        showToast('Invoice berhasil dibuat. Tekan tombol Refresh untuk memuat data terbaru.');
    } catch (err) {
        showToast(err.message || 'Gagal membuat invoice', 'error');
    }
}

async function approveInvoice(orderId) {
    try {
        await apiFetch(`/service-order/${orderId}/approve-invoice`, 'POST');
        showToast('Invoice disetujui. Tekan tombol Refresh untuk memuat data terbaru.');
    } catch (err) {
        showToast(err.message || 'Gagal menyetujui invoice', 'error');
    }
}

async function legacyShowOrderDetail(orderId) {
    try {
        const res = await apiFetch(`/service-order/${orderId}`);
        const body = document.getElementById('orderDetailBody');
        const o = res.order;
        const history = res.history || [];

        const details = (o.service_details || []).map(d => `${d.pk_type} ${d.brand} × ${d.quantity}`).join(', ');
        const invoiceInfo = o.invoice ? `<a href="/service-order/${o.id}/invoice" target="_blank" class="btn btn-sm btn-primary">Lihat Invoice</a>` : '<span class="text-muted">Belum ada invoice</span>';
        const spkLink = `<a href="/service-order/${o.id}/spk" target="_blank" class="btn btn-sm btn-secondary">Lihat SPK</a>`;
        const statusLabel = labelForServiceOrderStatus(o.status);
        const serviceDate = formatServiceDate(o.service_date);

        const historyHtml = history.length
            ? history.map(h => `
                <div class="timeline-item">
                    <div class="timeline-icon" style="background:${h.color}">
                        <i class="${h.icon}"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-label">${h.label}</div>
                        <div class="timeline-actor">${h.actor} <span class="role-badge">${h.role}</span></div>
                        ${h.notes ? `<div class="timeline-notes">${h.notes}</div>` : ''}
                        <div class="timeline-time">${h.time}</div>
                    </div>
                </div>
            `).join('')
            : '<div class="empty-state"><i class="fas fa-stream"></i><p>Belum ada log</p></div>';

        body.innerHTML = `
            <div class="order-meta">
                <div class="order-num">${o.order_number}</div>
                <div class="text-muted">${o.masjid.name}</div>
                <div class="status-badge status-${o.status}">${statusLabel}</div>
            </div>
            <div class="order-grid">
                <div><strong>Tanggal Servis</strong><br>${serviceDate}</div>
                <div><strong>Kontak</strong><br>${o.phone}</div>
                <div><strong>Detail</strong><br>${details || '–'}</div>
                <div><strong>Dokumen</strong><br>${spkLink} &nbsp; ${invoiceInfo}</div>
            </div>
            <div class="section-title" style="margin-top:1rem">Riwayat & Audit</div>
            <div class="timeline-container">${historyHtml}</div>
        `;

        openPopup('orderDetailPopup');
    } catch (err) {
        showToast(err.message || 'Gagal memuat detail', 'error');
    }
}

// Session expiry warning removed - manual refresh encouraged via UI button only
// if (document.querySelector('#monitoringSyncRoot')) {
//     setTimeout(() => {
//         showToast('Untuk performa terbaik, refresh halaman setiap 30 menit atau gunakan tombol Refresh.', 'info');
//     }, 25 * 60 * 1000);  // 25min warning
// }

// Override legacy detail rendering with escaped, production-safe markup.
async function showOrderDetail(orderId) {
    try {
        const res = await apiFetch(`/service-order/${orderId}`);
        const body = document.getElementById('orderDetailBody');
        const order = res.order;
        const history = res.history || [];

        const statusLabel = monitoringSafeText(labelForServiceOrderStatus(order.status));
        const serviceDate = monitoringSafeText(formatServiceDate(order.service_date));
        const orderNumber = monitoringSafeText(order.order_number);
        const masjidName = monitoringSafeText(order.masjid?.name);
        const phone = monitoringSafeText(order.phone || '-');
        const notes = monitoringSafeText(order.notes || '');

        const detailsHtml = (order.service_details || []).length
            ? order.service_details.map((detail) => `
                <span class="detail-chip">${monitoringSafeText(detail.pk_type)} ${monitoringSafeText(detail.brand)} x ${monitoringSafeText(detail.quantity)}</span>
            `).join('')
            : '<span class="text-muted">Belum ada detail unit.</span>';

        const invoiceAction = order.invoice
            ? `<a href="/service-order/${order.id}/invoice" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">Lihat Invoice</a>`
            : '<span class="text-muted">Belum ada invoice</span>';
        const spkAction = `<a href="/service-order/${order.id}/spk" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-secondary">Lihat SPK</a>`;

        const historyHtml = history.length
            ? history.map((item) => `
                <div class="timeline-item">
                    <div class="timeline-icon" style="background:${safeCssColor(item.color)}">
                        <i class="${item.icon}"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-label">${monitoringSafeText(item.label)}</div>
                        <div class="timeline-actor">${monitoringSafeText(item.actor)} <span class="role-badge">${monitoringSafeText(item.role)}</span></div>
                        ${item.notes ? `<div class="timeline-notes">${monitoringSafeText(item.notes)}</div>` : ''}
                        <div class="timeline-time">${monitoringSafeText(item.time)}</div>
                    </div>
                </div>
            `).join('')
            : '<div class="empty-state"><i class="fas fa-stream"></i><p>Belum ada log audit.</p></div>';

        body.innerHTML = `
            <div class="order-meta">
                <div class="popup-title-group">
                    <span class="popup-kicker">Audit Snapshot</span>
                    <div class="order-num">${orderNumber}</div>
                    <div class="text-muted">${masjidName}</div>
                </div>
                <div class="status-badge status-${monitoringSafeText(order.status)}">${statusLabel}</div>
            </div>
            <div class="popup-section-grid">
                <div class="detail-definition">
                    <span>Tanggal servis</span>
                    <strong>${serviceDate}</strong>
                </div>
                <div class="detail-definition">
                    <span>Kontak PIC</span>
                    <strong>${phone}</strong>
                </div>
                <div class="detail-definition">
                    <span>Status</span>
                    <strong>${statusLabel}</strong>
                </div>
                <div class="detail-definition">
                    <span>Dokumen</span>
                    <div class="action-btns action-btns--dense">${spkAction} ${invoiceAction}</div>
                </div>
            </div>
            <div class="detail-definition detail-definition--full">
                <span>Detail unit</span>
                <div class="detail-chip-stack">${detailsHtml}</div>
            </div>
            ${notes ? `<div class="popup-note-card"><strong>Instruksi Tambahan</strong><br>${notes}</div>` : ''}
            <div class="section-title" style="margin-top:1rem">
                <h2>Riwayat dan audit trail</h2>
            </div>
            <div class="timeline-container">${historyHtml}</div>
        `;

        openPopup('orderDetailPopup');
    } catch (err) {
        showToast(err.message || 'Gagal memuat detail', 'error');
    }
}

window.showOrderDetail = showOrderDetail;
window.ShowOrderDetail = showOrderDetail;

