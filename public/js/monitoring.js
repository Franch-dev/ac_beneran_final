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

// Simpan payload terakhir untuk dipakai saat user konfirmasi replace
let _lastPayload = null;

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
    document.getElementById('soDkmName').textContent       = dkm;
    document.getElementById('soMarbotName').textContent    = marbot;
    document.getElementById('soPhone').value               = phones[0] || '';

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
        showToast('Service Order berhasil dibuat!');
        setTimeout(function() { location.reload(); }, 1500);
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
    var statusLabel = existing.status === 'approved' ? 'Approved' : 'Pending';
    var statusColor = existing.status === 'approved' ? 'var(--success)' : 'var(--warning)';

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
        showToast('Order lama diganti, Service Order baru berhasil dibuat!');
        setTimeout(function() { location.reload(); }, 1500);
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
async function approveOrder(id) {
    if (!confirm('Setujui service order ini?')) return;
    try {
        await apiFetch(ROUTES_MON.soApprove(id), 'POST');
        showToast('Order berhasil diapprove!');
        setTimeout(function() { location.reload(); }, 1500);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// === BATALKAN APPROVE ===
async function cancelApprove(id) {
    if (!confirm('Batalkan approve order ini? Status akan kembali ke Pending.')) return;
    try {
        await apiFetch(ROUTES_MON.soCancel(id), 'POST');
        showToast('Approve dibatalkan, status kembali ke Pending');
        setTimeout(function() { location.reload(); }, 1500);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// === HAPUS ORDER ===
async function deleteOrder(id) {
    if (!confirm('Hapus service order ini?')) return;
    try {
        await apiFetch(ROUTES_MON.soDeleteMgr(id), 'DELETE');
        showToast('Service order dihapus');
        setTimeout(function() { location.reload(); }, 1500);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// === DETAIL ORDER ===
async function showOrderDetail(orderId) {
    var rowBtn = document.querySelector('tr [onclick="showOrderDetail(' + orderId + ')"]');
    if (!rowBtn) return;
    var row   = rowBtn.closest('tr');
    var cells = row.querySelectorAll('td');
    var body  = document.getElementById('orderDetailBody');

    body.innerHTML =
        '<div class="table-container" style="margin-bottom:1rem">' +
            '<table class="data-table">' +
                '<tr><th>No. Order</th><td>'   + (cells[0] ? cells[0].innerHTML : '') + '</td></tr>' +
                '<tr><th>Masjid</th><td>'       + (cells[1] ? cells[1].innerHTML : '') + '</td></tr>' +
                '<tr><th>Tgl. Servis</th><td>'  + (cells[2] ? cells[2].innerHTML : '') + '</td></tr>' +
                '<tr><th>Detail Unit</th><td>'  + (cells[3] ? cells[3].innerHTML : '') + '</td></tr>' +
                '<tr><th>Status</th><td>'       + (cells[4] ? cells[4].innerHTML : '') + '</td></tr>' +
                '<tr><th>Urgensi</th><td>'      + (cells[5] ? cells[5].innerHTML : '') + '</td></tr>' +
            '</table>' +
        '</div>' +
        '<div class="popup-actions">' +
            '<button class="btn btn-secondary" onclick="closePopup(\'orderDetailPopup\')">Tutup</button>' +
        '</div>';

    openPopup('orderDetailPopup');
}

// === RIWAYAT ORDER ===
async function showOrderHistory() {
    if (!selectedMasjidData) return;

    try {
        var orders = await apiFetch(ROUTES_MON.soHistory(selectedMasjidData.id));

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
                    .map(function(d) { return d.pk_type + ' ' + d.brand + ' \u00d7' + d.quantity; })
                    .join(', ');
                var tgl = new Date(o.service_date).toLocaleDateString('id-ID', {
                    day: '2-digit', month: 'short', year: 'numeric'
                });
                var statusCap = o.status.charAt(0).toUpperCase() + o.status.slice(1);
                html +=
                    '<tr>' +
                        '<td><span class="order-num">' + o.order_number + '</span></td>' +
                        '<td>' + tgl + '</td>' +
                        '<td><span class="status-badge status-' + o.status + '">' + statusCap + '</span></td>' +
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
