@php
    $popupAction = old('guest_order_action', $formActionRoute ?? route('modules.ac-service.guest-order.store'));
    $oldMasjidId = old('masjid_id');
    $oldDetails = old('details', []);
    if (! is_array($oldDetails) || $oldDetails === []) {
        $oldDetails = [[]];
    }

    $guestMasjidPayloads = collect();
    foreach ($masjids as $masjid) {
        $acUnitsArray = [];
        foreach ($masjid->acUnits as $unit) {
            $acUnitsArray[] = [
                'pk_type' => $unit->pk_type,
                'brand' => $unit->brand,
                'quantity' => $unit->quantity,
            ];
        }
        $guestMasjidPayloads[$masjid->id] = [
            'phones' => $masjid->phone_numbers,
            'ac_units' => $acUnitsArray,
        ];
    }
@endphp

<div class="popup popup-xl" id="guestOrderPopup" style="max-width: 1080px;">
    <div class="popup-header">
        <div>
            <span class="popup-kicker">Permintaan Servis</span>
            <h3 class="guest-order-popup-title">{{ $popupTitle ?? 'Formulir Service Order' }}</h3>
            <p>Silakan kirimkan permintaan servis AC tanpa harus masuk. Tim kami akan menindaklanjuti secepatnya.</p>
        </div>
        <button class="popup-close" type="button" onclick="closePopup('guestOrderPopup')" aria-label="Tutup popup">&times;</button>
    </div>
    <div class="popup-body popup-two-col">
        <div class="popup-col-left">
            <h4>Pilih Masjid</h4>
            <div class="search-input-wrap" style="margin-bottom: 0.75rem">
                <i class="fas fa-search"></i>
                <input type="text" id="guestSoMasjidSearch" class="search-input" placeholder="Cari masjid...">
            </div>
            <div class="masjid-select-list" id="guestMasjidSelectList">
                @foreach($masjids as $masjid)
                    <div
                        class="masjid-select-item{{ (string) $oldMasjidId === (string) $masjid->id ? ' selected' : '' }}"
                        data-id="{{ $masjid->id }}"
                        data-name="{{ $masjid->name }}"
                        data-address="{{ $masjid->address }}"
                        data-dkm="{{ $masjid->dkm_name }}"
                        data-marbot="{{ $masjid->marbot_name }}"
                        data-type="{{ $masjid->type }}"
                        data-phone='@json($guestMasjidPayloads[$masjid->id]["phones"] ?? [])'
                        data-ac='@json($guestMasjidPayloads[$masjid->id]["ac_units"] ?? [])'
                        onclick="selectMasjidForGuestSO(this)"
                    >
                        <div class="msi-id">{{ $masjid->custom_id }}</div>
                        <div class="msi-name">{{ $masjid->name }}</div>
                        <div class="msi-units">{{ $masjid->acUnits->sum('quantity') }} unit AC</div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="popup-col-right">
            @if(session('success'))
                <div class="alert alert-success glass-card" style="margin-bottom: 1rem; padding: 1rem;">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger glass-card" style="margin-bottom: 1rem; padding: 1rem;">
                    <strong>Perhatikan beberapa hal berikut:</strong>
                    <ul style="margin-top: 0.75rem; margin-left: 1rem;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="guestOrderForm" action="{{ $popupAction }}" method="POST">
                @csrf
                <input type="hidden" name="guest_order_action" id="guest_order_action" value="{{ old('guest_order_action', $popupAction) }}">
                <input type="hidden" name="masjid_id" id="guest_so_masjid_id" value="{{ $oldMasjidId }}">

                <div id="guestSoFormContent" style="{{ $oldMasjidId ? '' : 'display:none' }}">
                    <h4 id="guestSoMasjidName">{{ optional($masjids->firstWhere('id', $oldMasjidId))->name }}</h4>
                    <p id="guestSoMasjidAddress" class="text-muted text-sm">{{ optional($masjids->firstWhere('id', $oldMasjidId))->address }}</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="guest_so_meeting_person">Ditemui oleh</label>
                            <select id="guest_so_meeting_person" name="meeting_person" class="form-select" required>
                                <option value="dkm" {{ old('meeting_person', 'dkm') === 'dkm' ? 'selected' : '' }}>DKM</option>
                                <option value="marbot" {{ old('meeting_person') === 'marbot' ? 'selected' : '' }}>Marbot</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="guest_so_phone">Nomor HP</label>
                            <input type="text" id="guest_so_phone" name="phone" class="form-input" placeholder="Nomor HP..." value="{{ old('phone') }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Rincian Unit Servis</label>
                        <div id="guestSoDetailsList"></div>
                        <button type="button" class="btn btn-sm btn-outline" onclick="addGuestSODetail()">
                            <i class="fas fa-plus"></i> Tambah Unit
                        </button>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="guest_so_service_date">Tanggal Rencana Servis</label>
                        <input type="date" id="guest_so_service_date" name="service_date" class="form-input" min="{{ date('Y-m-d') }}" value="{{ old('service_date') }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="guest_so_notes">Instruksi Tambahan</label>
                        <textarea id="guest_so_notes" name="notes" class="form-textarea" rows="2" placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                    </div>

                    <div id="guestSoHargaInfo" class="info-banner" style="display:none;margin-top:0.5rem;font-size:0.78rem"></div>

                    <div class="so-total-preview">
                        <span><i class="fas fa-receipt"></i> Estimasi Total</span>
                        <span id="guestSoTotalPreview">-</span>
                    </div>

                    <div class="popup-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Kirim Order
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closePopup('guestOrderPopup')">
                            Batal
                        </button>
                    </div>
                </div>
                <div id="guestSoEmptyState" class="empty-state" style="{{ $oldMasjidId ? 'display:none' : '' }}">
                    <i class="fas fa-hand-pointer"></i>
                    <p>Pilih masjid dari daftar kiri</p>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        window.guestSODetailIndex = 0;
        window.guestSOOldDetails = @json(array_values($oldDetails));
        window.guestSOPriceMap = {
            masjid: { '1PK': 40000, '2PK': 45000, '5PK': 80000 },
            musholla: { '1PK': 40000, '2PK': 45000, '5PK': 80000 },
        };

        function guestSOFormatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(Number(amount || 0));
        }

        function guestSOParseJson(value, fallback) {
            try {
                return JSON.parse(value);
            } catch (error) {
                return fallback;
            }
        }

        function guestSOSelectedMasjidCard() {
            return document.querySelector('#guestMasjidSelectList .masjid-select-item.selected');
        }

        function guestSOListHasMeaningfulDetails() {
            return Array.from(document.querySelectorAll('#guestSoDetailsList .so-detail-row')).some((row) => {
                const pkType = row.querySelector('[data-role="pk-type"]')?.value || '';
                const brand = row.querySelector('[data-role="brand"]')?.value || '';
                const quantity = row.querySelector('[data-role="quantity"]')?.value || '';

                return pkType !== '' || brand.trim() !== '' || quantity !== '1';
            });
        }

        function guestSORenderTotal() {
            const selected = guestSOSelectedMasjidCard();
            const totalNode = document.getElementById('guestSoTotalPreview');
            const infoNode = document.getElementById('guestSoHargaInfo');
            const rows = document.querySelectorAll('#guestSoDetailsList .so-detail-row');

            if (!totalNode) {
                return;
            }

            if (!selected || rows.length === 0) {
                totalNode.textContent = '-';
                if (infoNode) {
                    infoNode.style.display = 'none';
                }
                return;
            }

            const type = selected.dataset.type || 'masjid';
            const prices = window.guestSOPriceMap[type] || window.guestSOPriceMap.masjid;
            let total = 0;
            const summaries = [];

            rows.forEach((row) => {
                const pkType = row.querySelector('[data-role="pk-type"]')?.value || '';
                const quantity = Number(row.querySelector('[data-role="quantity"]')?.value || 0);
                const price = Number(prices[pkType] || 0);

                if (pkType && quantity > 0 && price > 0) {
                    total += price * quantity;
                    summaries.push(`${pkType}: ${guestSOFormatCurrency(price)}/unit`);
                }
            });

            totalNode.textContent = total > 0 ? guestSOFormatCurrency(total) : '-';

            if (!infoNode) {
                return;
            }

            if (summaries.length > 0) {
                infoNode.innerHTML = `<i class="fas fa-circle-info"></i> ${[...new Set(summaries)].join(' | ')}`;
                infoNode.style.display = 'block';
            } else {
                infoNode.style.display = 'none';
            }
        }

        window.removeGuestSODetail = function (index) {
            document.querySelector(`#guestSoDetailsList [data-detail-index="${index}"]`)?.remove();
            guestSORenderTotal();
        };

        window.addGuestSODetail = function (detail = {}) {
            const list = document.getElementById('guestSoDetailsList');
            if (!list) {
                return;
            }

            const index = window.guestSODetailIndex++;
            const row = document.createElement('div');
            row.className = 'so-detail-row ac-unit-row';
            row.dataset.detailIndex = index;
            row.innerHTML = `
                <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeGuestSODetail(${index})">
                    <i class="fas fa-times"></i>
                </button>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Jenis Unit</label>
                        <select name="details[${index}][pk_type]" class="form-select" data-role="pk-type" required>
                            <option value="">Pilih ukuran</option>
                            <option value="1PK">1 PK</option>
                            <option value="2PK">2 PK</option>
                            <option value="5PK">5 PK</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Merek</label>
                        <input type="text" name="details[${index}][brand]" class="form-input" data-role="brand" placeholder="Contoh: Panasonic" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah</label>
                        <input type="number" min="1" name="details[${index}][quantity]" class="form-input" data-role="quantity" value="1" required>
                    </div>
                </div>
            `;

            list.appendChild(row);

            const pkField = row.querySelector('[data-role="pk-type"]');
            const brandField = row.querySelector('[data-role="brand"]');
            const quantityField = row.querySelector('[data-role="quantity"]');

            if (detail.pk_type) {
                pkField.value = detail.pk_type;
            }

            if (detail.brand) {
                brandField.value = detail.brand;
            }

            quantityField.value = detail.quantity || 1;

            [pkField, quantityField].forEach((field) => {
                field.addEventListener('change', guestSORenderTotal);
                field.addEventListener('input', guestSORenderTotal);
            });

            guestSORenderTotal();
        };

        window.selectMasjidForGuestSO = function (element) {
            document.querySelectorAll('#guestMasjidSelectList .masjid-select-item').forEach((item) => item.classList.remove('selected'));
            element.classList.add('selected');

            const masjidIdField = document.getElementById('guest_so_masjid_id');
            const formContent = document.getElementById('guestSoFormContent');
            const emptyState = document.getElementById('guestSoEmptyState');
            const nameNode = document.getElementById('guestSoMasjidName');
            const addressNode = document.getElementById('guestSoMasjidAddress');
            const phoneField = document.getElementById('guest_so_phone');
            const meetingField = document.getElementById('guest_so_meeting_person');
            const detailsList = document.getElementById('guestSoDetailsList');

            if (masjidIdField) {
                masjidIdField.value = element.dataset.id || '';
            }

            if (nameNode) {
                nameNode.textContent = element.dataset.name || '';
            }

            if (addressNode) {
                addressNode.textContent = element.dataset.address || '';
            }

            if (formContent) {
                formContent.style.display = '';
            }

            if (emptyState) {
                emptyState.style.display = 'none';
            }

            const phones = guestSOParseJson(element.dataset.phone || '[]', []);
            if (phoneField && !phoneField.value && Array.isArray(phones) && phones.length > 0) {
                phoneField.value = phones[0];
            }

            if (meetingField) {
                const dkmName = (element.dataset.dkm || '').trim();
                const marbotName = (element.dataset.marbot || '').trim();
                meetingField.value = !dkmName && marbotName ? 'marbot' : (meetingField.value || 'dkm');
            }

            if (detailsList && !guestSOListHasMeaningfulDetails()) {
                detailsList.innerHTML = '';
                const acUnits = guestSOParseJson(element.dataset.ac || '[]', []);
                if (Array.isArray(acUnits) && acUnits.length > 0) {
                    acUnits.forEach((unit) => addGuestSODetail(unit));
                } else {
                    addGuestSODetail();
                }
            }

            guestSORenderTotal();
        };

        window.openGuestOrderPopup = function (action, moduleLabel = 'AC Service') {
            const popup = document.getElementById('guestOrderPopup');
            if (!popup) {
                return;
            }

            const form = popup?.querySelector('form');
            const hiddenAction = popup?.querySelector('#guest_order_action');
            const title = popup?.querySelector('.guest-order-popup-title');

            if (form && action) {
                form.action = action;
            }

            if (hiddenAction && action) {
                hiddenAction.value = action;
            }

            if (title) {
                title.textContent = 'Formulir Service Order - ' + moduleLabel;
            }

            openPopup('guestOrderPopup');
        };

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-guest-order-action]').forEach((button) => {
                if (button.dataset.guestOrderBound === 'true') {
                    return;
                }

                button.dataset.guestOrderBound = 'true';
                button.addEventListener('click', function () {
                    window.openGuestOrderPopup?.(this.dataset.guestOrderAction, this.dataset.guestOrderLabel || 'AC Service');
                });
            });

            document.getElementById('guestSoMasjidSearch')?.addEventListener('input', function () {
                const keyword = this.value.trim().toLowerCase();
                document.querySelectorAll('#guestMasjidSelectList .masjid-select-item').forEach((item) => {
                    const haystack = [
                        item.dataset.name || '',
                        item.querySelector('.msi-id')?.textContent || '',
                    ].join(' ').toLowerCase();

                    item.style.display = haystack.includes(keyword) ? '' : 'none';
                });
            });

            if (window.guestSOOldDetails.length > 0) {
                window.guestSOOldDetails.forEach((detail) => addGuestSODetail(detail));
            } else {
                addGuestSODetail();
            }

            const selectedCard = guestSOSelectedMasjidCard();
            if (selectedCard) {
                selectMasjidForGuestSO(selectedCard);
            }

            const hasGuestOrderErrors = {{ $errors->any() && old('guest_order_action') ? 'true' : 'false' }};
            if (hasGuestOrderErrors) {
                openGuestOrderPopup('{{ $popupAction }}', @js($popupTitle ?? 'Service Order'));
            }
        });
    </script>
@endpush
