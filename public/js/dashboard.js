/* ==========================================
   DASHBOARD.JS — Masjid & AC Management
   ========================================== */

// Phone fields
function addPhoneField() {
    const container = document.getElementById('phoneList');
    const div = document.createElement('div');
    div.className = 'phone-input-row';
    div.innerHTML = `
        <input type="text" name="phone_numbers[]" class="form-input" placeholder="+62..." required>
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">
            <i class="fas fa-minus"></i>
        </button>
    `;
    container.appendChild(div);
}

function addEditPhoneField(value = '') {
    const container = document.getElementById('editPhoneList');
    const div = document.createElement('div');
    div.className = 'phone-input-row';
    div.innerHTML = `
        <input type="text" class="form-input edit-phone" placeholder="+62..." value="${value}">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">
            <i class="fas fa-minus"></i>
        </button>
    `;
    container.appendChild(div);
}

function resetMasjidForm() {
    document.getElementById('addMasjidForm').reset();
    const phoneList = document.getElementById('phoneList');
    phoneList.innerHTML = `
        <div class="phone-input-row">
            <input type="text" name="phone_numbers[]" class="form-input" placeholder="+62..." required>
            <button type="button" class="btn btn-sm btn-success" onclick="addPhoneField()">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    `;
}

// === ADD MASJID ===
document.getElementById('addMasjidForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = this.querySelector('[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

    try {
        const formData = new FormData(this);
        const phones = formData.getAll('phone_numbers[]');
        const data = {
            type: formData.get('type'),
            name: formData.get('name'),
            address: formData.get('address'),
            dkm_name: formData.get('dkm_name'),
            marbot_name: formData.get('marbot_name'),
            phone_numbers: phones.filter(p => p.trim()),
        };

        const res = await apiFetch(ROUTES.masjidStore, 'POST', data);
        closePopup('addMasjidPopup');
        resetMasjidForm();
        showToast(`Masjid berhasil didaftarkan! ID: ${res.custom_id}`);

        // Show AC popup
        document.getElementById('acMasjidId').value = res.masjid.id;
        document.getElementById('acUnitsList').innerHTML = '';
        addACUnit();
        openPopup('addACPopup');

    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Daftarkan';
    }
});

// === ADD AC UNIT (dynamic row) ===
const brands = ['Samsung', 'LG', 'Daikin', 'Mitsubishi', 'Sharp', 'Panasonic', 'Gree', 'Aqua', 'Haier', 'Toshiba'];

function addACUnit() {
    const container = document.getElementById('acUnitsList');
    const index = container.children.length;
    const div = document.createElement('div');
    div.className = 'ac-unit-row';
    div.innerHTML = `
        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">PK</label>
                <select class="form-select ac-pk">
                    <option value="1PK">1 PK</option>
                    <option value="2PK">2 PK</option>
                    <option value="5PK">5 PK</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Merk</label>
                <select class="form-select ac-brand">
                    ${brands.map(b => `<option value="${b}">${b}</option>`).join('')}
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Jumlah Unit</label>
                <input type="number" class="form-input ac-qty" min="1" value="1">
            </div>
            <div class="form-group">
                <label class="form-label">Terakhir Servis</label>
                <input type="date" class="form-input ac-date">
            </div>
        </div>
    `;
    container.appendChild(div);
}

async function saveACUnits() {
    const masjidId = document.getElementById('acMasjidId').value;
    const rows = document.querySelectorAll('#acUnitsList .ac-unit-row');

    if (!rows.length) {
        showToast('Tambahkan setidaknya satu unit AC', 'error');
        return;
    }

    const units = [];
    let valid = true;
    rows.forEach(row => {
        const pk = row.querySelector('.ac-pk').value;
        const brand = row.querySelector('.ac-brand').value;
        const qty = parseInt(row.querySelector('.ac-qty').value);
        const date = row.querySelector('.ac-date').value;

        if (!pk || !brand || !qty || qty < 1) { valid = false; return; }
        units.push({ pk_type: pk, brand, quantity: qty, last_service_date: date || null });
    });

    if (!valid) { showToast('Lengkapi data AC dengan benar', 'error'); return; }

    try {
        await apiFetch(ROUTES.acBulk, 'POST', { masjid_id: masjidId, units });
        closePopup('addACPopup');
        showToast('Data AC berhasil disimpan!');
        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// === VIEW DETAIL ===
async function showDetail(masjidId) {
    try {
        const data = await apiFetch(ROUTES.masjidDetail(masjidId));
        const body = document.getElementById('detailACBody');

        if (!data.ac_units || data.ac_units.length === 0) {
            body.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-snowflake"></i>
                    <p>Belum ada unit AC terdaftar</p>
                </div>
            `;
        } else {
            let html = `
                <div style="margin-bottom: 1rem">
                    <strong>${data.name}</strong> <span class="text-muted">(${data.custom_id})</span>
                </div>
                <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>PK</th>
                            <th>Merk</th>
                            <th>Jumlah</th>
                            <th>Terakhir Servis</th>
                            <th>Hari Lalu</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.ac_units.forEach(unit => {
                const days = unit.last_service_date
                    ? Math.floor((new Date() - new Date(unit.last_service_date)) / 86400000)
                    : '–';
                const urgency = days === '–' ? '' : days < 90 ? 'aman' : days <= 120 ? 'harus_servis' : 'overdue';
                const urgencyText = urgency === 'aman' ? '✅ Aman' : urgency === 'harus_servis' ? '⚠️ Harus Servis' : urgency === 'overdue' ? '🔴 Overdue' : '–';
                html += `
                    <tr>
                        <td>${unit.pk_type}</td>
                        <td>${unit.brand}</td>
                        <td>${unit.quantity} unit</td>
                        <td>${unit.last_service_date ? new Date(unit.last_service_date).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' }) : '–'}</td>
                        <td>${days}</td>
                        <td><span class="urgency-text-${urgency}">${urgencyText}</span></td>
                    </tr>
                `;
            });
            html += '</tbody></table></div>';
            body.innerHTML = html;
        }

        openPopup('detailACPopup');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// === EDIT MASJID ===
function openEditMasjid(id, name, address, dkm, marbot, phones) {
    document.getElementById('editMasjidId').value = id;
    document.getElementById('editMasjidName').value = name;
    document.getElementById('editMasjidAddress').value = address;
    document.getElementById('editMasjidDkm').value = dkm;
    document.getElementById('editMasjidMarbot').value = marbot;

    const container = document.getElementById('editPhoneList');
    container.innerHTML = '';
    (phones || []).forEach(p => addEditPhoneField(p));
    if (!phones || !phones.length) addEditPhoneField();

    openPopup('editMasjidPopup');
}

document.getElementById('editMasjidForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const id = document.getElementById('editMasjidId').value;
    const phones = [...document.querySelectorAll('.edit-phone')].map(i => i.value).filter(p => p.trim());

    if (!phones.length) { showToast('Minimal 1 nomor HP', 'error'); return; }

    try {
        await apiFetch(ROUTES.masjidUpdate(id), 'PUT', {
            name: document.getElementById('editMasjidName').value,
            address: document.getElementById('editMasjidAddress').value,
            dkm_name: document.getElementById('editMasjidDkm').value,
            marbot_name: document.getElementById('editMasjidMarbot').value,
            phone_numbers: phones,
        });
        closePopup('editMasjidPopup');
        showToast('Data masjid berhasil diperbarui!');
        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        showToast(err.message, 'error');
    }
});

// === EDIT AC ===
async function openEditAC(masjidId) {
    try {
        const data = await apiFetch(ROUTES.masjidDetail(masjidId));
        const body = document.getElementById('editACBody');

        if (!data.ac_units || data.ac_units.length === 0) {
            body.innerHTML = `
                <div class="empty-state" style="padding: 1rem">
                    <p>Belum ada unit AC. Tambahkan unit baru:</p>
                </div>
                <input type="hidden" id="editACMasjidId" value="${masjidId}">
                <div id="newACList"></div>
                <button class="btn btn-outline btn-sm" onclick="addNewAC()"><i class="fas fa-plus"></i> Tambah AC</button>
                <div class="popup-actions">
                    <button class="btn btn-primary" onclick="saveNewACs()">Simpan</button>
                </div>
            `;
        } else {
            let html = `<input type="hidden" id="editACMasjidId" value="${masjidId}">`;
            data.ac_units.forEach(unit => {
                html += `
                    <div class="ac-unit-row" data-id="${unit.id}">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">PK</label>
                                <input class="form-input" value="${unit.pk_type}" disabled>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Merk</label>
                                <input class="form-input eu-brand" value="${unit.brand}">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Jumlah</label>
                                <input type="number" class="form-input eu-qty" value="${unit.quantity}" min="1">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Terakhir Servis</label>
                                <input type="date" class="form-input eu-date" value="${unit.last_service_date || ''}">
                            </div>
                        </div>
                        <div style="display:flex;gap:0.5rem;margin-top:0.5rem">
                            <button class="btn btn-sm btn-success" onclick="saveOneAC(${unit.id}, this)">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteAC(${unit.id}, this)">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                `;
            });
            html += `
                <div id="newACList"></div>
                <button class="btn btn-outline btn-sm" onclick="addNewAC()"><i class="fas fa-plus"></i> Tambah Unit Baru</button>
                <div class="popup-actions">
                    <button class="btn btn-secondary" onclick="saveNewACs()">Simpan Unit Baru</button>
                    <button class="btn btn-secondary" onclick="closePopup('editACPopup')">Tutup</button>
                </div>
            `;
            body.innerHTML = html;
        }

        openPopup('editACPopup');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

function addNewAC() {
    const container = document.getElementById('newACList');
    const div = document.createElement('div');
    div.className = 'ac-unit-row';
    div.innerHTML = `
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">PK</label>
                <select class="form-select new-ac-pk">
                    <option value="1PK">1 PK</option>
                    <option value="2PK">2 PK</option>
                    <option value="5PK">5 PK</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Merk</label>
                <select class="form-select new-ac-brand">
                    ${brands.map(b => `<option value="${b}">${b}</option>`).join('')}
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Jumlah</label>
                <input type="number" class="form-input new-ac-qty" min="1" value="1">
            </div>
            <div class="form-group">
                <label class="form-label">Terakhir Servis</label>
                <input type="date" class="form-input new-ac-date">
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i> Hapus
        </button>
    `;
    container.appendChild(div);
}

async function saveOneAC(unitId, btn) {
    const row = btn.closest('.ac-unit-row');
    try {
        btn.disabled = true;
        await apiFetch(ROUTES.acUpdate(unitId), 'PUT', {
            brand: row.querySelector('.eu-brand').value,
            quantity: parseInt(row.querySelector('.eu-qty').value),
            last_service_date: row.querySelector('.eu-date').value || null,
        });
        showToast('Unit AC berhasil diperbarui!');
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        btn.disabled = false;
    }
}

async function deleteAC(unitId, btn) {
    if (!confirm('Hapus unit AC ini?')) return;
    try {
        await apiFetch(ROUTES.acDestroy(unitId), 'DELETE');
        btn.closest('.ac-unit-row').remove();
        showToast('Unit AC dihapus');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

async function saveNewACs() {
    const masjidId = document.getElementById('editACMasjidId').value;
    const rows = document.querySelectorAll('#newACList .ac-unit-row');
    if (!rows.length) { closePopup('editACPopup'); return; }

    const units = [];
    rows.forEach(row => {
        units.push({
            pk_type: row.querySelector('.new-ac-pk').value,
            brand: row.querySelector('.new-ac-brand').value,
            quantity: parseInt(row.querySelector('.new-ac-qty').value),
            last_service_date: row.querySelector('.new-ac-date').value || null,
        });
    });

    try {
        await apiFetch(ROUTES.acBulk, 'POST', { masjid_id: masjidId, units });
        showToast('Unit baru berhasil ditambahkan!');
        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// === DELETE MASJID ===
let deleteId = null;

function confirmDelete(id, name) {
    deleteId = id;
    document.getElementById('deleteName').textContent = name;
    document.getElementById('deleteConfirmBtn').onclick = async () => {
        try {
            await apiFetch(ROUTES.masjidDestroy(deleteId), 'DELETE');
            closePopup('deletePopup');
            showToast('Masjid berhasil dihapus');
            setTimeout(() => location.reload(), 1500);
        } catch (err) {
            showToast(err.message, 'error');
        }
    };
    openPopup('deletePopup');
}

// === SEARCH FILTER (live) ===
const searchInput = document.querySelector('.search-input');
if (searchInput) {
    searchInput.addEventListener('input', function () {
        const val = this.value.toLowerCase();
        document.querySelectorAll('.masjid-card').forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(val) ? '' : 'none';
        });
    });
}
