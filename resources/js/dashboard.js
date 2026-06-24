/* ==========================================
   DASHBOARD.JS - Masjid & AC Management
   ========================================== */

const dashboardSafeText = window.escapeHtml || ((value) => String(value ?? ''));
const brands = ['Samsung', 'LG', 'Daikin', 'Mitsubishi', 'Sharp', 'Panasonic', 'Gree', 'Aqua', 'Haier', 'Toshiba'];
const onboardingState = {
    masjid: null,
};

async function refreshDashboardSurface() {
    if (typeof window.scheduleCurrentPageSnapshot === 'function') {
        window.scheduleCurrentPageSnapshot();
        return;
    }

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

async function confirmDashboardAction(options = {}) {
    if (typeof window.confirmAction === 'function') {
        const result = await window.confirmAction(options);
        return result?.confirmed || result === true;
    }

    return true;
}

function bindDashboardSearchFilter() {
    const searchInput = document.querySelector('.search-input');
    if (!searchInput || searchInput.dataset.liveFilterBound === 'true') {
        return;
    }

    searchInput.dataset.liveFilterBound = 'true';
    searchInput.addEventListener('input', function () {
        const value = this.value.toLowerCase();
        document.querySelectorAll('.masjid-card').forEach((card) => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(value) ? '' : 'none';
        });
    });
}

window.dashboardRealtimeAfterRender = function () {
    bindDashboardSearchFilter();
};

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
        <input type="text" class="form-input edit-phone" placeholder="+62..." value="${dashboardSafeText(value)}">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">
            <i class="fas fa-minus"></i>
        </button>
    `;
    container.appendChild(div);
}

function resetMasjidForm() {
    document.getElementById('addMasjidForm')?.reset();
    const phoneList = document.getElementById('phoneList');
    if (!phoneList) {
        return;
    }

    phoneList.innerHTML = `
        <div class="phone-input-row">
            <input type="text" name="phone_numbers[]" class="form-input" placeholder="+62..." required>
            <button type="button" class="btn btn-sm btn-success" onclick="addPhoneField()">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    `;
}

function prepareACOnboarding(masjid) {
    onboardingState.masjid = masjid;
    document.getElementById('acMasjidId').value = masjid.id;
    document.getElementById('acUnitsList').innerHTML = '';
    document.getElementById('acMasjidMeta').textContent = `${masjid.name} (${masjid.custom_id}) berhasil didaftarkan.`;
    addACUnit();
}

function clearACOnboarding() {
    onboardingState.masjid = null;
    const masjidId = document.getElementById('acMasjidId');
    const acUnitsList = document.getElementById('acUnitsList');
    const acMeta = document.getElementById('acMasjidMeta');

    if (masjidId) {
        masjidId.value = '';
    }

    if (acUnitsList) {
        acUnitsList.innerHTML = '';
    }

    if (acMeta) {
        acMeta.textContent = 'Masjid berhasil didaftarkan.';
    }
}

window.skipACSetup = async function () {
    const confirmed = await confirmDashboardAction({
        type: 'warning',
        heading: 'Lewati setup AC?',
        message: 'Masjid akan tersimpan tanpa unit AC. Data AC bisa dilengkapi nanti dari Kelola AC.',
        confirmText: 'Ya, Lewati',
    });

    if (!confirmed) {
        return;
    }

    closePopup('addACPopup');
    clearACOnboarding();
    showToast('Setup AC disimpan sebagai pending. Anda bisa melengkapinya nanti dari Kelola AC.', 'info');
    await refreshDashboardSurface();
};

document.getElementById('addMasjidForm')?.addEventListener('submit', async function (event) {
    event.preventDefault();

    try {
        const formData = new FormData(this);
        const phones = formData.getAll('phone_numbers[]').filter((phone) => phone.trim());
        const data = {
            type: formData.get('type'),
            name: formData.get('name'),
            address: formData.get('address'),
            dkm_name: formData.get('dkm_name'),
            marbot_name: formData.get('marbot_name'),
            phone_numbers: phones,
        };

        const confirmed = await confirmDashboardAction({
            type: 'success',
            heading: 'Daftarkan masjid?',
            message: 'Data masjid akan disimpan dan Anda akan diarahkan untuk menambahkan unit AC.',
            confirmText: 'Ya, Daftarkan',
            details: [
                { label: 'Nama', value: data.name || '-' },
                { label: 'Tipe', value: data.type || '-' },
                { label: 'Nomor HP', value: phones.length ? `${phones.length} nomor` : '-' },
            ],
        });

        if (!confirmed) {
            return;
        }

        const button = this.querySelector('[type="submit"]');
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

        const response = await apiFetch(ROUTES.masjidStore, 'POST', data);
        await refreshDashboardSurface();
        resetMasjidForm();
        prepareACOnboarding(response.masjid);
        closePopup('addMasjidPopup');
        openPopup('addACPopup');
        showToast(`Masjid berhasil didaftarkan! ID: ${response.custom_id}`);
    } catch (error) {
        showToast(error.message, 'error');
    } finally {
        const button = this.querySelector('[type="submit"]');
        if (button) {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-check"></i> Daftarkan';
        }
    }
});

function addACUnit() {
    const container = document.getElementById('acUnitsList');
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
                    ${brands.map((brand) => `<option value="${brand}">${brand}</option>`).join('')}
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

    rows.forEach((row) => {
        const pk = row.querySelector('.ac-pk').value;
        const brand = row.querySelector('.ac-brand').value;
        const qty = parseInt(row.querySelector('.ac-qty').value, 10);
        const date = row.querySelector('.ac-date').value;

        if (!pk || !brand || !qty || qty < 1) {
            valid = false;
            return;
        }

        units.push({ pk_type: pk, brand, quantity: qty, last_service_date: date || null });
    });

    if (!valid) {
        showToast('Lengkapi data AC dengan benar', 'error');
        return;
    }

    const confirmed = await confirmDashboardAction({
        type: 'success',
        heading: 'Simpan data AC?',
        message: 'Unit AC akan ditambahkan ke masjid yang baru didaftarkan.',
        confirmText: 'Ya, Simpan',
        details: [
            { label: 'Masjid', value: onboardingState.masjid?.name || masjidId || '-' },
            { label: 'Jumlah Baris', value: `${units.length} data` },
            { label: 'Total Unit', value: `${units.reduce((sum, unit) => sum + Number(unit.quantity || 0), 0)} unit` },
        ],
    });

    if (!confirmed) {
        return;
    }

    try {
        await apiFetch(ROUTES.acBulk, 'POST', { masjid_id: masjidId, units });
        closePopup('addACPopup');
        clearACOnboarding();
        showToast('Data AC berhasil disimpan!');
        await refreshDashboardSurface();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

async function showDetail(masjidId) {
    try {
        const data = await apiFetch(ROUTES.masjidDetail(masjidId));
        const body = document.getElementById('detailACBody');
        const locationName = dashboardSafeText(data.name);
        const locationId = dashboardSafeText(data.custom_id);

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
                    <strong>${locationName}</strong> <span class="text-muted">(${locationId})</span>
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

            data.ac_units.forEach((unit) => {
                const days = unit.last_service_date
                    ? Math.floor((new Date() - new Date(unit.last_service_date)) / 86400000)
                    : '-';
                const urgency = days === '-' ? '' : days < 90 ? 'aman' : days <= 120 ? 'harus_servis' : 'overdue';
                const urgencyText = urgency === 'aman' ? 'Aman' : urgency === 'harus_servis' ? 'Harus Servis' : urgency === 'overdue' ? 'Overdue' : '-';
                html += `
                    <tr>
                        <td>${dashboardSafeText(unit.pk_type)}</td>
                        <td>${dashboardSafeText(unit.brand)}</td>
                        <td>${unit.quantity} unit</td>
                        <td>${unit.last_service_date ? new Date(unit.last_service_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'}</td>
                        <td>${days}</td>
                        <td><span class="urgency-text-${urgency}">${urgencyText}</span></td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            body.innerHTML = html;
        }

        openPopup('detailACPopup');
    } catch (error) {
        showToast(error.message, 'error');
    }
}

function openEditMasjid(idOrPayload, name, address, dkm, marbot, phones) {
    const payload = typeof idOrPayload === 'object' && idOrPayload !== null
        ? idOrPayload
        : { id: idOrPayload, name, address, dkm, marbot, phones };

    document.getElementById('editMasjidId').value = payload.id ?? '';
    document.getElementById('editMasjidName').value = payload.name ?? '';
    document.getElementById('editMasjidAddress').value = payload.address ?? '';
    document.getElementById('editMasjidDkm').value = payload.dkm ?? '';
    document.getElementById('editMasjidMarbot').value = payload.marbot ?? '';

    const container = document.getElementById('editPhoneList');
    container.innerHTML = '';
    const phoneValues = Array.isArray(payload.phones) ? payload.phones : [];
    phoneValues.forEach((phone) => addEditPhoneField(phone));
    if (!phoneValues.length) {
        addEditPhoneField();
    }

    openPopup('editMasjidPopup');
}

document.getElementById('editMasjidForm')?.addEventListener('submit', async function (event) {
    event.preventDefault();

    const id = document.getElementById('editMasjidId').value;
    const phones = [...document.querySelectorAll('.edit-phone')]
        .map((input) => input.value)
        .filter((phone) => phone.trim());

    if (!phones.length) {
        showToast('Minimal 1 nomor HP', 'error');
        return;
    }

    const confirmed = await confirmDashboardAction({
        type: 'success',
        heading: 'Simpan perubahan masjid?',
        message: 'Data profil masjid akan diperbarui.',
        confirmText: 'Ya, Simpan',
        details: [
            { label: 'Nama', value: document.getElementById('editMasjidName').value || '-' },
            { label: 'DKM', value: document.getElementById('editMasjidDkm').value || '-' },
            { label: 'Nomor HP', value: `${phones.length} nomor` },
        ],
    });

    if (!confirmed) {
        return;
    }

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
        await refreshDashboardSurface();
    } catch (error) {
        showToast(error.message, 'error');
    }
});

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
            data.ac_units.forEach((unit) => {
                html += `
                    <div class="ac-unit-row" data-id="${unit.id}">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">PK</label>
                                <input class="form-input" value="${dashboardSafeText(unit.pk_type)}" disabled>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Merk</label>
                                <input class="form-input eu-brand" value="${dashboardSafeText(unit.brand)}">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Jumlah</label>
                                <input type="number" class="form-input eu-qty" value="${unit.quantity}" min="1">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Terakhir Servis</label>
                                <input type="date" class="form-input eu-date" value="${dashboardSafeText(unit.last_service_date || '')}">
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
    } catch (error) {
        showToast(error.message, 'error');
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
                    ${brands.map((brand) => `<option value="${brand}">${brand}</option>`).join('')}
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

async function saveOneAC(unitId, button) {
    const row = button.closest('.ac-unit-row');
    const confirmed = await confirmDashboardAction({
        type: 'success',
        heading: 'Simpan perubahan AC?',
        message: 'Data unit AC ini akan diperbarui.',
        confirmText: 'Ya, Simpan',
        details: [
            { label: 'Merk', value: row.querySelector('.eu-brand')?.value || '-' },
            { label: 'Jumlah', value: row.querySelector('.eu-qty')?.value || '-' },
            { label: 'Terakhir Servis', value: row.querySelector('.eu-date')?.value || '-' },
        ],
    });

    if (!confirmed) {
        return;
    }

    try {
        button.disabled = true;
        await apiFetch(ROUTES.acUpdate(unitId), 'PUT', {
            brand: row.querySelector('.eu-brand').value,
            quantity: parseInt(row.querySelector('.eu-qty').value, 10),
            last_service_date: row.querySelector('.eu-date').value || null,
        });
        showToast('Unit AC berhasil diperbarui!');
        await refreshDashboardSurface();
    } catch (error) {
        showToast(error.message, 'error');
    } finally {
        button.disabled = false;
    }
}

async function deleteAC(unitId, button) {
    const row = button.closest('.ac-unit-row');
    const confirmed = await confirmDashboardAction({
        type: 'danger',
        heading: 'Hapus unit AC?',
        message: 'Unit AC ini akan dihapus dari data masjid.',
        confirmText: 'Ya, Hapus',
        details: [
            { label: 'Merk', value: row?.querySelector('.eu-brand')?.value || '-' },
            { label: 'Jumlah', value: row?.querySelector('.eu-qty')?.value || '-' },
        ],
    });

    if (!confirmed) {
        return;
    }

    try {
        await apiFetch(ROUTES.acDestroy(unitId), 'DELETE');
        row?.remove();
        showToast('Unit AC dihapus');
        await refreshDashboardSurface();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

async function saveNewACs() {
    const masjidId = document.getElementById('editACMasjidId').value;
    const rows = document.querySelectorAll('#newACList .ac-unit-row');

    if (!rows.length) {
        closePopup('editACPopup');
        return;
    }

    const units = [];
    rows.forEach((row) => {
        units.push({
            pk_type: row.querySelector('.new-ac-pk').value,
            brand: row.querySelector('.new-ac-brand').value,
            quantity: parseInt(row.querySelector('.new-ac-qty').value, 10),
            last_service_date: row.querySelector('.new-ac-date').value || null,
        });
    });

    const confirmed = await confirmDashboardAction({
        type: 'success',
        heading: 'Simpan unit AC baru?',
        message: 'Unit AC baru akan ditambahkan ke masjid ini.',
        confirmText: 'Ya, Simpan',
        details: [
            { label: 'Jumlah Baris', value: `${units.length} data` },
            { label: 'Total Unit', value: `${units.reduce((sum, unit) => sum + Number(unit.quantity || 0), 0)} unit` },
        ],
    });

    if (!confirmed) {
        return;
    }

    try {
        await apiFetch(ROUTES.acBulk, 'POST', { masjid_id: masjidId, units });
        showToast('Unit baru berhasil ditambahkan!');
        await refreshDashboardSurface();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

let deleteId = null;

async function confirmDelete(id, name) {
    deleteId = id;
    const confirmed = await confirmDashboardAction({
        type: 'danger',
        heading: 'Hapus masjid?',
        message: 'Data masjid dan unit AC terkait akan dihapus.',
        confirmText: 'Ya, Hapus',
        details: [
            { label: 'Masjid', value: name || '-' },
        ],
    });

    if (!confirmed) {
        return;
    }

    try {
        await apiFetch(ROUTES.masjidDestroy(deleteId), 'DELETE');
        closePopup('deletePopup');
        showToast('Masjid berhasil dihapus');
        await refreshDashboardSurface();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

bindDashboardSearchFilter();

Object.assign(window, {
    addPhoneField,
    resetMasjidForm,
    addACUnit,
    saveACUnits,
    showDetail,
    openEditMasjid,
    openEditAC,
    addNewAC,
    saveOneAC,
    deleteAC,
    saveNewACs,
    confirmDelete,
});
