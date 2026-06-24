<div class="popup popup-lg" id="guestOrderPopup" style="max-width: 640px;">
    <div class="popup-header">
        <div>
            <span class="popup-kicker">Permintaan Servis</span>
            <h3 class="guest-order-popup-title">{{ $popupTitle ?? 'Formulir Service Order' }}</h3>
            <p>Silakan kirimkan permintaan servis AC tanpa harus masuk. Tim kami akan menindaklanjuti secepatnya.</p>
        </div>
        <button class="popup-close" type="button" onclick="closePopup('guestOrderPopup')" aria-label="Tutup popup">&times;</button>
    </div>
    <div class="popup-body">
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

        <form id="guestOrderForm" action="{{ old('guest_order_action', $formActionRoute ?? route('modules.ac-service.guest-order.store')) }}" method="POST"
            data-confirm="Service order guest akan dikirim untuk ditinjau frontdesk."
            data-confirm-heading="Kirim guest order?"
            data-confirm-type="success"
            data-confirm-text="Ya, Kirim">
            @csrf
            <input type="hidden" name="guest_order_action" id="guest_order_action" value="{{ old('guest_order_action', $formActionRoute ?? route('modules.ac-service.guest-order.store')) }}">

            <div class="form-group">
                <label class="form-label" for="guest_so_name">Nama Pelapor</label>
                <input type="text" id="guest_so_name" name="reporter_name" class="form-input" placeholder="Nama lengkap..." value="{{ old('reporter_name') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="guest_so_masjid_name">Nama Masjid/Musholla</label>
                <input type="text" id="guest_so_masjid_name" name="masjid_name" class="form-input" placeholder="Contoh: Masjid Al-Ikhlas" value="{{ old('masjid_name') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="guest_so_masjid_address">Alamat Lokasi</label>
                <textarea id="guest_so_masjid_address" name="masjid_address" class="form-textarea" rows="2" placeholder="Alamat lengkap masjid/musholla..." required>{{ old('masjid_address') }}</textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="guest_so_phone">Nomor HP</label>
                    <input type="tel" id="guest_so_phone" name="phone" class="form-input" placeholder="0812xxxx..." value="{{ old('phone') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="guest_so_meeting_person">Ditemui oleh</label>
                    <select id="guest_so_meeting_person" name="meeting_person" class="form-select" required>
                        <option value="dkm" {{ old('meeting_person', 'dkm') === 'dkm' ? 'selected' : '' }}>DKM</option>
                        <option value="marbot" {{ old('meeting_person') === 'marbot' ? 'selected' : '' }}>Marbot</option>
                    </select>
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
                <textarea id="guest_so_notes" name="notes" class="form-textarea" rows="3" placeholder="Contoh: Filter kotor, suara berisik, atau pendinginan kurang maksimal.">{{ old('notes') }}</textarea>
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
        </form>
    </div>
</div>

@push('scripts')
    <script>
        window.guestSODetailIndex = 0;
        window.guestSOOldDetails = @json(old('details', []));

        function guestSOFormatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(Number(amount || 0));
        }

        function guestSORenderTotal() {
            const totalNode = document.getElementById('guestSoTotalPreview');
            const infoNode = document.getElementById('guestSoHargaInfo');
            const rows = document.querySelectorAll('#guestSoDetailsList .so-detail-row');

            if (!totalNode) {
                return;
            }

            const prices = { '1PK': 40000, '2PK': 45000, '5PK': 80000 };
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

            const oldDetails = window.guestSOOldDetails;
            if (oldDetails && oldDetails.length > 0) {
                oldDetails.forEach((detail) => addGuestSODetail(detail));
            } else {
                addGuestSODetail();
            }

            const hasGuestOrderErrors = {{ $errors->any() && old('guest_order_action') ? 'true' : 'false' }};
            if (hasGuestOrderErrors) {
                openGuestOrderPopup('{{ old('guest_order_action', $formActionRoute ?? route('modules.ac-service.guest-order.store')) }}', @js($popupTitle ?? 'Service Order'));
            }
        });
    </script>
@endpush
