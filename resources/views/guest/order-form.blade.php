@extends('layouts.app')

@section('title', 'Order Service AC - AC Beneran')

@section('content')
<style>
    .guest-page-wrap {
        max-width: 720px;
        margin: 0 auto;
        padding: 32px 24px;
    }
    .guest-form-card {
        background: var(--bg-card);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-lg);
        padding: 28px;
    }
    @media (max-width: 640px) {
        .guest-page-wrap { padding: 20px 16px; }
        .guest-form-card { padding: 20px; border-radius: var(--radius-lg); }
    }
    .guest-header {
        text-align: center;
        margin-bottom: 28px;
    }
    .guest-header h1 {
        font-size: clamp(1.5rem, 4vw, 2rem);
        font-weight: 700;
        color: var(--text);
        letter-spacing: -0.02em;
    }
    .guest-header p {
        font-size: 0.9375rem;
        color: var(--text-muted);
        margin-top: 6px;
    }
    .autocomplete-results {
        position: absolute;
        z-index: 50;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        background: var(--bg-card);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1.5px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow-md);
        display: none;
    }
    .autocomplete-results.active { display: block; }
    .autocomplete-item {
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid var(--border-light);
        transition: background var(--t);
    }
    .autocomplete-item:hover { background: var(--primary-soft); }
    .autocomplete-item:last-child { border-bottom: none; }
    .autocomplete-item .ac-name {
        font-weight: 600;
        color: var(--text);
        font-size: 0.875rem;
    }
    .autocomplete-item .ac-detail {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 2px;
    }
    .honeypot { position: absolute; left: -9999px; opacity: 0; }
    .selected-masjid-card {
        background: var(--primary-soft);
        border: 1px solid var(--primary-mid);
        border-radius: var(--radius);
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .selected-masjid-card .sm-name {
        font-weight: 600;
        color: var(--primary);
        font-size: 0.875rem;
    }
    .selected-masjid-card .sm-address {
        font-size: 0.8125rem;
        color: var(--text-muted);
        margin-top: 2px;
    }
    .form-hint {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 4px;
    }
    .manual-link {
        font-size: 0.8125rem;
        color: var(--primary);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 8px;
        transition: color var(--t);
    }
    .manual-link:hover { color: var(--primary-hover); }
    .form-footer {
        text-align: center;
        margin-top: 24px;
        font-size: 0.8125rem;
        color: var(--text-muted);
        line-height: 1.6;
    }
    .spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid rgba(255,255,255,0.25);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<div class="guest-page-wrap">
    <!-- Header -->
    <div class="guest-header">
        <h1><i class="fas fa-snowflake" style="color: var(--primary); margin-right: 8px;"></i>Order Service AC</h1>
        <p>Isi form berikut untuk memesan service AC</p>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle" style="margin-right: 8px;"></i>{{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <!-- Form -->
    <form id="guestOrderForm" action="{{ route('guest.order.store') }}" method="POST" class="guest-form-card">
        @csrf

        <!-- Honeypot -->
        <div class="honeypot" aria-hidden="true">
            <label for="website">Website</label>
            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
        </div>

        <!-- Guest Name -->
        <div class="form-group">
            <label for="guest_name" class="form-label">Nama Lengkap <span class="required">*</span></label>
            <input type="text" name="guest_name" id="guest_name" value="{{ old('guest_name') }}" required
                class="form-input"
                placeholder="Masukkan nama Anda">
        </div>

        <!-- Phone -->
        <div class="form-group">
            <label for="guest_phone" class="form-label">Nomor Telepon <span class="required">*</span></label>
            <input type="tel" name="guest_phone" id="guest_phone" value="{{ old('guest_phone') }}" required
                class="form-input"
                placeholder="08xxxxxxxxxx">
            <p class="form-hint">Format: 08xxxxxxxxxx</p>

            <!-- New phone number checkbox -->
            <div class="mt-2" style="margin-top: 10px;">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_new_phone" id="is_new_phone" value="1"
                        {{ old('is_new_phone') ? 'checked' : '' }}>
                    Ini nomor baru / nomor pengganti
                </label>
            </div>

            <!-- Additional phone description -->
            <div id="phoneDescriptionGroup" class="hidden" style="margin-top: 10px; display: none;">
                <label for="additional_phone_description" class="form-label">Keterangan nomor telepon</label>
                <textarea name="additional_phone_description" id="additional_phone_description" rows="2"
                    class="form-textarea"
                    placeholder="Contoh: Nomor baru, nomor pengganti, dll.">{{ old('additional_phone_description') }}</textarea>
            </div>
        </div>

        <!-- Masjid Search -->
        <div class="form-group" style="position: relative;">
            <label for="masjid_search" class="form-label">Masjid / Musholla <span class="required">*</span></label>
            <input type="text" id="masjid_search" value="{{ old('masjid_name') }}"
                class="form-input"
                placeholder="Ketik nama masjid untuk mencari..."
                autocomplete="off">
            <input type="hidden" name="masjid_id" id="masjid_id" value="{{ old('masjid_id') }}">
            <input type="hidden" name="masjid_name" id="masjid_name" value="{{ old('masjid_name') }}">

            <!-- Autocomplete results -->
            <div id="masjidAutocomplete" class="autocomplete-results"></div>

            <!-- Selected masjid display -->
            <div id="selectedMasjid" class="hidden" style="margin-top: 10px; display: none;">
                <div class="selected-masjid-card">
                    <div>
                        <p class="sm-name" id="selectedMasjidName"></p>
                        <p class="sm-address" id="selectedMasjidAddress"></p>
                    </div>
                    <button type="button" onclick="clearMasjid()" class="btn btn-ghost btn-sm" style="color: var(--primary);">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Manual masjid entry -->
            <div id="manualMasjidEntry" class="hidden" style="margin-top: 12px; display: none;">
                <p class="form-hint" style="margin-bottom: 8px;">Masjid tidak ditemukan? Isi manual:</p>
                <input type="text" name="masjid_name_manual" id="masjid_name_manual"
                    class="form-input" style="margin-bottom: 10px;"
                    placeholder="Nama Masjid / Musholla">
                <textarea name="address" id="address" rows="2"
                    class="form-textarea"
                    placeholder="Alamat lengkap">{{ old('address') }}</textarea>
            </div>

            <button type="button" id="showManualEntry" onclick="showManualMasjidEntry()"
                class="manual-link">
                Masjid tidak ditemukan? Isi manual <i class="fas fa-arrow-right"></i>
            </button>
        </div>

        <!-- AC Type -->
        <div class="form-group">
            <label for="ac_type" class="form-label">Tipe AC <span class="required">*</span></label>
            <select name="ac_type" id="ac_type" required class="form-select">
                <option value="">Pilih tipe AC</option>
                <option value="1PK" {{ old('ac_type') == '1PK' ? 'selected' : '' }}>1 PK</option>
                <option value="2PK" {{ old('ac_type') == '2PK' ? 'selected' : '' }}>2 PK</option>
                <option value="5PK" {{ old('ac_type') == '5PK' ? 'selected' : '' }}>5 PK</option>
            </select>
        </div>

        <!-- AC Amount -->
        <div class="form-group">
            <label for="ac_amount" class="form-label">Jumlah Unit AC <span class="required">*</span></label>
            <input type="number" name="ac_amount" id="ac_amount" value="{{ old('ac_amount', 1) }}" min="1" max="50" required
                class="form-input">
        </div>

        <!-- Problem Description -->
        <div class="form-group">
            <label for="problem_description" class="form-label">Deskripsi Masalah <span class="required">*</span></label>
            <textarea name="problem_description" id="problem_description" rows="4" required
                class="form-textarea"
                placeholder="Jelaskan masalah AC Anda...">{{ old('problem_description') }}</textarea>
        </div>

        <!-- Submit -->
        <div style="margin-top: 24px;">
            <button type="submit" id="submitBtn"
                class="btn btn-primary btn-lg btn-block">
                <span id="submitText">Kirim Permintaan Service</span>
                <span id="submitLoading" class="hidden">
                    <span class="spinner"></span>
                </span>
            </button>
        </div>
    </form>

    <!-- Info -->
    <div class="form-footer">
        <p>Permintaan Anda akan ditinjau oleh tim kami dalam 1x24 jam.</p>
        <p>Hubungi kami jika ada pertanyaan.</p>
    </div>
</div>

<script>
    // Phone description toggle
    const isNewPhone = document.getElementById('is_new_phone');
    const phoneDescGroup = document.getElementById('phoneDescriptionGroup');

    isNewPhone.addEventListener('change', function() {
        phoneDescGroup.style.display = this.checked ? 'block' : 'none';
    });

    // Initialize on page load
    if (isNewPhone.checked) {
        phoneDescGroup.style.display = 'block';
    }

    // Masjid search autocomplete
    const masjidSearch = document.getElementById('masjid_search');
    const masjidAutocomplete = document.getElementById('masjidAutocomplete');
    const masjidIdInput = document.getElementById('masjid_id');
    const masjidNameInput = document.getElementById('masjid_name');
    const selectedMasjid = document.getElementById('selectedMasjid');
    const manualEntry = document.getElementById('manualMasjidEntry');
    const showManualBtn = document.getElementById('showManualEntry');

    let searchTimeout;

    masjidSearch.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 2) {
            masjidAutocomplete.classList.remove('active');
            return;
        }

        searchTimeout = setTimeout(() => {
            fetchMasjids(query);
        }, 300);
    });

    async function fetchMasjids(query) {
        try {
            const response = await fetch(`/api/masjids/search?q=${encodeURIComponent(query)}`);
            const data = await response.json();

            if (data.length === 0) {
                masjidAutocomplete.innerHTML = '<div class="autocomplete-item"><div class="ac-detail">Tidak ditemukan. Klik "Isi manual" di bawah.</div></div>';
            } else {
                masjidAutocomplete.innerHTML = data.map(m => `
                    <div class="autocomplete-item" onclick="selectMasjid(${m.id}, '${escapeHtml(m.name)}', '${escapeHtml(m.address || '')}')">
                        <div class="ac-name">${escapeHtml(m.name)}</div>
                        <div class="ac-detail">${escapeHtml(m.type)} &bull; ${escapeHtml(m.address || 'Alamat tidak tersedia')}</div>
                    </div>
                `).join('');
            }

            masjidAutocomplete.classList.add('active');
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    function selectMasjid(id, name, address) {
        masjidIdInput.value = id;
        masjidNameInput.value = name;
        masjidSearch.value = name;
        masjidSearch.classList.add('hidden');
        showManualBtn.classList.add('hidden');
        manualEntry.classList.add('hidden');

        document.getElementById('selectedMasjidName').textContent = name;
        document.getElementById('selectedMasjidAddress').textContent = address || 'Alamat tidak tersedia';
        selectedMasjid.classList.remove('hidden');

        masjidAutocomplete.classList.remove('active');
    }

    function clearMasjid() {
        masjidIdInput.value = '';
        masjidNameInput.value = '';
        masjidSearch.value = '';
        masjidSearch.classList.remove('hidden');
        showManualBtn.classList.remove('hidden');
        selectedMasjid.classList.add('hidden');
        manualEntry.classList.add('hidden');
    }

    function showManualMasjidEntry() {
        manualEntry.classList.remove('hidden');
        showManualBtn.classList.add('hidden');
        masjidAutocomplete.classList.remove('active');
    }

    // Update masjid_name from manual entry
    const masjidNameManual = document.getElementById('masjid_name_manual');
    if (masjidNameManual) {
        masjidNameManual.addEventListener('input', function() {
            masjidNameInput.value = this.value;
        });
    }

    // Form submission
    const form = document.getElementById('guestOrderForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const submitLoading = document.getElementById('submitLoading');

    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitText.classList.add('hidden');
        submitLoading.classList.remove('hidden');
    });

    // Utility: escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r?\n/g, ' ');
    }

    // Close autocomplete when clicking outside
    document.addEventListener('click', function(e) {
        if (!masjidSearch.contains(e.target) && !masjidAutocomplete.contains(e.target)) {
            masjidAutocomplete.classList.remove('active');
        }
    });
</script>
@endsection
