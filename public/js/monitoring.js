/* ==========================================
   MONITORING.JS — Service Orders Management
   ========================================== */
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

let selectedMasjidData = null;
let soAcData = [];

// === MASJID SEARCH IN SO POPUP ===
document.getElementById('soMasjidSearch')?.addEventListener('input', function () {
    const val = this.value.toLowerCase();
    document.querySelectorAll('.masjid-select-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(val) ? '' : 'none';
    });
});

function selectMasjidForSO(el) {
    // Deselect all
    document.querySelectorAll('.masjid-select-item').forEach(i => i.classList.remove('selected'));
    el.classList.add('selected');

    const name = el.dataset.name;
    const address = el.dataset.address;
    const dkm = el.dataset.dkm;
    const marbot = el.dataset.marbot;
    const phones = JSON.parse(el.dataset.phone);
    soAcData = JSON.parse(el.dataset.ac);

    selectedMasjidData = { id: el.dataset.id, name, address, dkm, marbot, phones };

    // Fill form
    document.getElementById('soMasjidName').textContent = name;
    document.getElementById('soMasjidAddress').textContent = address;
    document.getElementById('soDkmName').textContent = dkm;
    document.getElementById('soMarbotName').textContent = marbot;
    document.getElementById('soPhone').value = phones[0] || '';

    // Reset detail rows
    document.getElementById('soDetailsList').innerHTML = '';
    addSODetail();

    document.getElementById('soFormContent').style.display = 'block';
    document.getElementById('soEmptyState').style.display = 'none';
}

// === SO DETAIL ROWS ===
function addSODetail() {
    const container = document.getElementById('soDetailsList');
    const div = document.createElement('div');
    div.className = 'so-detail-row';

    const pkOptions = ['1PK', '2PK', '5PK'].map(pk => `<option value="${pk}">${pk}</option>`).join('');

    div.innerHTML = `
        <div class="form-group" style="margin:0">
            <label class="form-label" style="font-size:0.75rem">PK</label>
            <select class="form-select so-pk" onchange="updateBrandOptions(this)">
                ${pkOptions}
            </select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label" style="font-size:0.75rem">Merk</label>
            <select class="form-select so-brand"></select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label" style="font-size:0.75rem">Qty</label>
            <input type="number" class="form-input so-qty" min="1" value="1">
        </div>
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()" style="align-self:flex-end;margin-bottom:1rem">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
    updateBrandOptions(div.querySelector('.so-pk'));
}

function updateBrandOptions(pkSelect) {
    const pk = pkSelect.value;
    const brandSelect = pkSelect.closest('.so-detail-row').querySelector('.so-brand');

    // Get brands available for this PK
    const available = soAcData
        .filter(u => u.pk_type === pk)
        .map(u => ({ brand: u.brand, qty: u.quantity }));

    brandSelect.innerHTML = available.length
        ? available.map(u => `<option value="${u.brand}" data-max="${u.qty}">${u.brand} (max: ${u.qty})</option>`).join('')
        : '<option value="">Tidak ada unit</option>';
}

// === SUBMIT SERVICE ORDER ===
async function submitServiceOrder() {
    if (!selectedMasjidData) {
        showToast('Pilih masjid terlebih dahulu', 'error');
        return;
    }

    const rows = document.querySelectorAll('#soDetailsList .so-detail-row');
    if (!rows.length) {
        showToast('Tambahkan minimal satu unit servis', 'error');
        return;
    }

    const details = [];
    let valid = true;

    rows.forEach(row => {
        const pk = row.querySelector('.so-pk').value;
        const brand = row.querySelector('.so-brand').value;
        const qty = parseInt(row.querySelector('.so-qty').value);
        const maxOption = row.querySelector('.so-brand option:checked');
        const max = maxOption ? parseInt(maxOption.dataset.max || 9999) : 9999;

        if (!pk || !brand || !qty || qty < 1) { valid = false; return; }
        if (qty > max) {
            showToast(`Jumlah ${pk} ${brand} melebihi unit tersedia (max: ${max})`, 'error');
            valid = false;
            return;
        }
        details.push({ pk_type: pk, brand, quantity: qty, price_per_unit: getPriceByPK(pk) });
    });

    if (!valid) return;

    const serviceDate = document.getElementById('soServiceDate').value;
    if (!serviceDate) {
        showToast('Pilih tanggal rencana servis', 'error');
        return;
    }

    try {
        const res = await apiFetch(ROUTES_MON.soStore, 'POST', {
            masjid_id: selectedMasjidData.id,
            meeting_person: document.getElementById('soMeetingPerson').value,
            phone: document.getElementById('soPhone').value,
            service_date: serviceDate,
            notes: document.getElementById('soNotes').value,
            details,
        });

        closePopup('serviceOrderPopup');
        showToast('Service Order berhasil dibuat!');
        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

function getPriceByPK(pk) {
    const prices = { '1PK': 150000, '2PK': 200000, '5PK': 350000 };
    return prices[pk] || 150000;
}

// === APPROVE ===
async function approveOrder(id) {
    if (!confirm('Setujui service order ini?')) return;
    try {
        await apiFetch(ROUTES_MON.soApprove(id), 'POST');
        showToast('Order berhasil diapprove!');
        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// === CANCEL APPROVE ===
async function cancelApprove(id) {
    if (!confirm('Batalkan approve order ini? Status akan kembali ke Pending.')) return;
    try {
        await apiFetch(ROUTES_MON.soCancel(id), 'POST');
        showToast('Approve dibatalkan, status kembali ke Pending');
        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// === DELETE ORDER ===
async function deleteOrder(id) {
    if (!confirm('Hapus service order ini?')) return;
    try {
        await apiFetch(ROUTES_MON.soDeleteMgr(id), 'DELETE');
        showToast('Service order dihapus');
        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// === ORDER DETAIL (inline) ===
async function showOrderDetail(orderId) {
    // Find from the DOM since data is already rendered
    const row = document.querySelector(`tr [onclick="showOrderDetail(${orderId})"]`)?.closest('tr');
    if (!row) return;

    const cells = row.querySelectorAll('td');
    const body = document.getElementById('orderDetailBody');

    body.innerHTML = `
        <div class="table-container" style="margin-bottom:1rem">
            <table class="data-table">
                <tr><th>No. Order</th><td>${cells[0]?.innerHTML || ''}</td></tr>
                <tr><th>Masjid</th><td>${cells[1]?.innerHTML || ''}</td></tr>
                <tr><th>Tgl. Servis</th><td>${cells[2]?.innerHTML || ''}</td></tr>
                <tr><th>Detail Unit</th><td>${cells[3]?.innerHTML || ''}</td></tr>
                <tr><th>Status</th><td>${cells[4]?.innerHTML || ''}</td></tr>
                <tr><th>Urgensi</th><td>${cells[5]?.innerHTML || ''}</td></tr>
            </table>
        </div>
        <div class="popup-actions">
            <button class="btn btn-secondary" onclick="closePopup('orderDetailPopup')">Tutup</button>
        </div>
    `;
    openPopup('orderDetailPopup');
}

// === ORDER HISTORY ===
async function showOrderHistory() {
    if (!selectedMasjidData) return;

    try {
        const orders = await apiFetch(ROUTES_MON.soHistory(selectedMasjidData.id));

        if (!orders.length) {
            document.getElementById('historyBody').innerHTML = `
                <div class="empty-state"><i class="fas fa-history"></i><p>Belum ada riwayat servis</p></div>
            `;
        } else {
            let html = '<div class="table-container"><table class="data-table"><thead><tr><th>No. Order</th><th>Tanggal</th><th>Status</th><th>Detail</th></tr></thead><tbody>';
            orders.forEach(o => {
                const statusClass = `status-${o.status}`;
                const details = (o.service_details || []).map(d => `${d.pk_type} ${d.brand} ×${d.quantity}`).join(', ');
                html += `<tr>
                    <td><span class="order-num">${o.order_number}</span></td>
                    <td>${new Date(o.service_date).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' })}</td>
                    <td><span class="status-badge ${statusClass}">${o.status.charAt(0).toUpperCase() + o.status.slice(1)}</span></td>
                    <td>${details || '–'}</td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            document.getElementById('historyBody').innerHTML = html;
        }

        openPopup('historyPopup');
    } catch (err) {
        showToast(err.message, 'error');
    }
}
