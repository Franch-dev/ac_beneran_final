@extends('layouts.app')

@section('title', 'Dashboard - AC Servis Masjid')

@section('content')
@php
    $visibleMasjids = $masjids->getCollection();
    $visibleUnits = $visibleMasjids->sum(fn ($masjid) => $masjid->acUnits->sum('quantity'));
    $activeOrders = $dashboardMetrics['active_orders'] ?? 0;
    $needsAttention = $dashboardMetrics['needs_attention_locations'] ?? 0;
    $searchTerm = request('search');
@endphp

<div id="dashboardSyncRoot">
<div class="page-container page-operations page-operations--dashboard">
    <section class="ops-hero ops-hero--dashboard glass-surface" data-aos="fade-down">
        <div class="ops-hero__copy">
            <span class="ops-hero__eyebrow">Masjid Operations Center</span>
            <div class="page-header page-header--hero">
                <div>
                    <h1 class="page-title"><i class="fas fa-th-large"></i> Dashboard Masjid</h1>
                    <p class="page-subtitle">Portofolio lokasi, urgensi servis, dan kontak operasional dalam satu panel.</p>
                </div>
                <div class="page-actions page-actions--hero">
                    <button class="btn btn-secondary" type="button" onclick="manualRefreshDashboard()">
                        <i class="fas fa-rotate-right"></i> Refresh Data
                    </button>
                    @if(auth()->user()->isFrontdesk())
                    <button class="btn btn-primary" onclick="openPopup('addMasjidPopup')">
                        <i class="fas fa-plus"></i> Tambah Masjid
                    </button>
                    @endif
                    <a href="{{ route('monitoring') }}" class="btn btn-outline">
                        <i class="fas fa-wave-square"></i> Buka Monitoring
                    </a>
                </div>
            </div>
            <p class="ops-hero__lead">
                Selamat datang, <strong>{{ auth()->user()->name }}</strong>. Gunakan kartu lokasi untuk meninjau kesiapan servis,
                PIC lapangan, dan order aktif tanpa berpindah halaman.
            </p>
            <div class="ops-chip-row">
                <span class="ops-chip">
                    <i class="fas fa-user-shield"></i>
                    <strong>{{ ucfirst(auth()->user()->role) }}</strong>
                </span>
                <span class="ops-chip">
                    <i class="fas fa-filter"></i>
                    {{ $searchTerm ? 'Filter aktif: "' . $searchTerm . '"' : 'Mode tampilan: semua lokasi' }}
                </span>
                <span class="ops-chip">
                    <i class="fas fa-bell"></i>
                    {{ $needsAttention }} lokasi perlu tindak lanjut
                </span>
                <span class="ops-chip">
                    <i class="fas fa-link-slash"></i>
                    {{ $dashboardMetrics['pending_setup_locations'] ?? 0 }} lokasi masih pending setup AC
                </span>
            </div>
        </div>
        <div class="ops-kpi-grid" data-stagger-group>
            <article class="ops-kpi-card" data-stagger-item>
                <span class="ops-kpi-card__label">Total Lokasi</span>
                <strong class="ops-kpi-card__value">{{ $dashboardMetrics['total_locations'] ?? $masjids->total() }}</strong>
                <span class="ops-kpi-card__meta">Masjid dan musholla terdaftar</span>
            </article>
            <article class="ops-kpi-card" data-stagger-item>
                <span class="ops-kpi-card__label">Unit Terpantau</span>
                <strong class="ops-kpi-card__value">{{ $dashboardMetrics['total_units'] ?? $visibleUnits }}</strong>
                <span class="ops-kpi-card__meta">Total unit AC lintas seluruh lokasi</span>
            </article>
            <article class="ops-kpi-card" data-stagger-item>
                <span class="ops-kpi-card__label">Order Aktif</span>
                <strong class="ops-kpi-card__value">{{ $activeOrders }}</strong>
                <span class="ops-kpi-card__meta">Service order lintas halaman yang masih berjalan</span>
            </article>
            <article class="ops-kpi-card ops-kpi-card--alert" data-stagger-item>
                <span class="ops-kpi-card__label">Perlu Follow-Up</span>
                <strong class="ops-kpi-card__value">{{ $needsAttention }}</strong>
                <span class="ops-kpi-card__meta">Status harus servis atau overdue di seluruh jaringan</span>
            </article>
        </div>
    </section>

    <div class="role-info-bar role-info-bar--elevated">
        <i class="fas fa-circle-info"></i>
        @if(auth()->user()->isManager())
        Anda dapat meninjau kesiapan lokasi di sini lalu menyetujui service order dari halaman Monitoring.
        @elseif(auth()->user()->isFrontdesk())
        Anda dapat menambah masjid, mengelola unit AC, dan menyiapkan lokasi sebelum membuat service order.
        @else
        Anda berada dalam mode baca. Semua data lokasi dan status servis dapat dipantau tanpa hak ubah.
        @endif
    </div>

    <section class="search-bar ops-control-bar" data-aos="fade-up" data-aos-delay="120">
        <div class="ops-control-bar__header">
            <div>
                <h2 class="ops-section-title">Cari Lokasi</h2>
                <p class="ops-section-copy">Telusuri lokasi berdasarkan ID masjid atau nama untuk mempercepat eksekusi operasional.</p>
            </div>
            <div class="ops-control-meta">
                <span class="notification-badge notification-badge--neutral">{{ $masjids->count() }} kartu tampil</span>
                <span class="notification-badge notification-badge--soft">Scroll untuk inspeksi cepat</span>
            </div>
        </div>
        <form action="{{ route('dashboard') }}" method="GET" class="search-form search-form--hero">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Cari ID atau nama masjid..."
                       value="{{ $searchTerm }}" class="search-input">
                @if($searchTerm)
                    <a href="{{ route('dashboard') }}" class="search-clear" aria-label="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>
        @if($searchTerm)
            <p class="search-result-info">Menampilkan hasil untuk: <strong>"{{ $searchTerm }}"</strong> ({{ $masjids->total() }} ditemukan)</p>
        @endif
    </section>

    <div class="cards-grid ops-card-grid" id="masjidGrid" data-stagger-group style="--cols-xs: 1; --cols-sm: 2;">
        @forelse($masjids as $masjid)
        @php
            $urgency = $masjid->urgency_status;
            $urgencyLabel = match($urgency) {
                'aman' => 'Aman',
                'harus_servis' => 'Harus Servis',
                'overdue' => 'Overdue',
                default => 'Belum Ada Data',
            };
            $phoneEntries = is_array($masjid->phone_numbers)
                ? array_filter($masjid->phone_numbers)
                : (filled($masjid->phone_numbers) ? [$masjid->phone_numbers] : []);
            $phoneSummary = collect($phoneEntries)->take(2)->implode(' · ');
            $lastServiceText = $masjid->max_days_since_service
                ? $masjid->max_days_since_service . ' hari lalu'
                : 'Belum ada catatan';
            $activeOrder = $masjid->serviceOrders->first(fn ($serviceOrder) => $serviceOrder->isActive());
        @endphp
        <article class="masjid-card ops-masjid-card urgency-{{ $urgency }} ui-reveal" data-id="{{ $masjid->id }}" data-stagger-item>
            <div class="card-accent-bar"></div>
            <div class="card-top">
                <div class="card-top__eyebrow">
                    <span class="card-type-chip {{ $masjid->type }}">
                        {{ $masjid->type === 'masjid' ? 'Masjid' : 'Musholla' }}
                    </span>
                    <span class="notification-badge {{ $activeOrder ? 'notification-badge--live' : 'notification-badge--neutral' }}">
                        <i class="fas {{ $activeOrder ? 'fa-bell-concierge' : 'fa-clock' }}"></i>
                        {{ $activeOrder ? 'Order aktif' : 'Siap dijadwalkan' }}
                    </span>
                </div>
                <span class="urgency-pill urgency-{{ $urgency }}">
                    <span class="urgency-pulse"></span>
                    {{ $urgencyLabel }}
                </span>
            </div>
            <div class="card-body">
                <div class="card-id-row">
                    <span class="card-id">{{ $masjid->custom_id }}</span>
                    <span class="card-counter">{{ $masjid->serviceOrders->count() }} order</span>
                </div>
                @if($masjid->setup_status === 'pending_ac')
                <span class="notification-badge notification-badge--warning">
                    <i class="fas fa-link-slash"></i> Pending setup AC
                </span>
                @endif
                <div class="card-name">{{ $masjid->name }}</div>
                <div class="card-address"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($masjid->address, 76) }}</div>
                <div class="masjid-contact-stack">
                    <span class="contact-chip"><i class="fas fa-user-tie"></i> {{ $masjid->dkm_name }}</span>
                    <span class="contact-chip"><i class="fas fa-user-gear"></i> {{ $masjid->marbot_name }}</span>
                </div>
                <div class="card-phone">
                    <i class="fas fa-phone"></i>
                    <span class="phone-number {{ $phoneSummary ? '' : 'text-muted' }}">
                        {{ $phoneSummary ?: 'Tidak ada nomor telepon' }}
                    </span>
                </div>
                <div class="masjid-card-metrics">
                    <div class="masjid-metric">
                        <span class="masjid-metric__label">Unit AC</span>
                        <strong class="masjid-metric__value">{{ $masjid->acUnits->sum('quantity') }}</strong>
                    </div>
                    <div class="masjid-metric">
                        <span class="masjid-metric__label">Servis Terakhir</span>
                        <strong class="masjid-metric__value">{{ $lastServiceText }}</strong>
                    </div>
                </div>
                @if($activeOrder)
                <span class="card-order-badge status-{{ $activeOrder->status }}">
                    <i class="fas fa-wave-square"></i>
                    {{ $activeOrder->order_number }} · {{ $activeOrder->service_date->format('d M Y') }}
                </span>
                @else
                <span class="card-order-badge card-order-badge--idle">
                    <i class="fas fa-calendar-plus"></i> Belum ada service order aktif
                </span>
                @endif
            </div>
            <div class="card-footer">
                <button class="btn btn-sm btn-info" type="button" onclick="showDetail({{ $masjid->id }})">
                    <i class="fas fa-eye"></i> Detail AC
                </button>
                @if(auth()->user()->isFrontdesk())
                <button class="btn btn-sm btn-warning" type="button" onclick="openEditAC({{ $masjid->id }})">
                    <i class="fas fa-tools"></i> Kelola AC
                </button>
                <button class="btn btn-sm btn-secondary" type="button" onclick="openEditMasjid({ id: {{ $masjid->id }}, name: @js($masjid->name), address: @js($masjid->address), dkm: @js($masjid->dkm_name), marbot: @js($masjid->marbot_name), phones: @js(array_values($phoneEntries)) })">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-danger" type="button" onclick="confirmDelete({{ $masjid->id }}, @js($masjid->name))">
                    <i class="fas fa-trash"></i>
                </button>
                @endif
            </div>
        </article>
        @empty
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-mosque"></i></div>
            <h3>Belum Ada Data Masjid</h3>
            <p>{{ $searchTerm ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Mulai dengan menambahkan masjid pertama.' }}</p>
            @if(auth()->user()->isFrontdesk() && !request('search'))
            <button class="btn btn-primary" onclick="openPopup('addMasjidPopup')">
                <i class="fas fa-plus"></i> Tambah Masjid
            </button>
            @endif
        </div>
        @endforelse
    </div>

    @if($masjids->hasPages())
    <div class="pagination-shell pagination-shell--fixed">
        {{ $masjids->onEachSide(1)->links() }}
    </div>
    @endif
</div>
</div>

<!-- =============== POPUPS =============== -->

<!-- Add Masjid Popup -->
<div class="popup popup-lg" id="addMasjidPopup">
    <div class="popup-header">
        <h3><i class="fas fa-mosque"></i> Daftarkan Masjid Baru</h3>
        <button class="popup-close" onclick="closePopup('addMasjidPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <form id="addMasjidForm">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tipe <span class="required">*</span></label>
                    <select name="type" id="masjidType" class="form-select" required>
                        <option value="masjid">Masjid</option>
                        <option value="musholla">Musholla</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama <span class="required">*</span></label>
                    <input type="text" name="name" class="form-input" placeholder="Nama masjid..." required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Alamat <span class="required">*</span></label>
                <textarea name="address" class="form-textarea" placeholder="Alamat lengkap..." required rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama DKM <span class="required">*</span></label>
                    <input type="text" name="dkm_name" class="form-input" placeholder="Ketua DKM..." required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Marbot <span class="required">*</span></label>
                    <input type="text" name="marbot_name" class="form-input" placeholder="Nama marbot..." required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Nomor HP <span class="required">*</span></label>
                <div id="phoneList">
                    <div class="phone-input-row">
                        <input type="text" name="phone_numbers[]" class="form-input" placeholder="+62..." required>
                        <button type="button" class="btn btn-sm btn-success" onclick="addPhoneField()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="popup-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Daftarkan
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetMasjidForm()">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add AC Popup (appears after add masjid) -->
<div class="popup popup-lg" id="addACPopup">
    <div class="popup-header">
        <h3><i class="fas fa-snowflake"></i> Tambah Data AC</h3>
        <button class="popup-close" onclick="closePopup('addACPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <div class="info-banner">
            <i class="fas fa-info-circle"></i>
            <span id="acMasjidMeta">Masjid berhasil didaftarkan.</span> Sekarang tambahkan data AC.
        </div>
        <input type="hidden" id="acMasjidId">
        <div id="acUnitsList">
            <!-- AC units will be added dynamically -->
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="addACUnit()">
            <i class="fas fa-plus"></i> Tambah Unit AC
        </button>
        <div class="popup-actions" style="margin-top: 1rem">
            <button type="button" class="btn btn-primary" onclick="saveACUnits()">
                <i class="fas fa-save"></i> Konfirmasi
            </button>
            <button type="button" class="btn btn-secondary" onclick="skipACSetup()">
                Lewati
            </button>
        </div>
    </div>
</div>

<!-- Detail AC Popup -->
<div class="popup popup-lg" id="detailACPopup">
    <div class="popup-header">
        <h3><i class="fas fa-list"></i> Detail Unit AC</h3>
        <button class="popup-close" onclick="closePopup('detailACPopup')">&times;</button>
    </div>
    <div class="popup-body" id="detailACBody">
        <!-- Dynamic content -->
    </div>
</div>

<!-- Edit Masjid Popup -->
<div class="popup popup-lg" id="editMasjidPopup">
    <div class="popup-header">
        <h3><i class="fas fa-edit"></i> Edit Data Masjid</h3>
        <button class="popup-close" onclick="closePopup('editMasjidPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <form id="editMasjidForm">
            <input type="hidden" id="editMasjidId">
            <div class="form-group">
                <label class="form-label">Nama</label>
                <input type="text" id="editMasjidName" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea id="editMasjidAddress" class="form-textarea" rows="2" required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama DKM</label>
                    <input type="text" id="editMasjidDkm" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Marbot</label>
                    <input type="text" id="editMasjidMarbot" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Nomor HP</label>
                <div id="editPhoneList"></div>
                <button type="button" class="btn btn-sm btn-success" onclick="addEditPhoneField()">
                    <i class="fas fa-plus"></i> Tambah Nomor
                </button>
            </div>
            <div class="popup-actions">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <button type="button" class="btn btn-secondary" onclick="closePopup('editMasjidPopup')">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit AC Popup -->
<div class="popup popup-lg" id="editACPopup">
    <div class="popup-header">
        <h3><i class="fas fa-tools"></i> Kelola Unit AC</h3>
        <button class="popup-close" onclick="closePopup('editACPopup')">&times;</button>
    </div>
    <div class="popup-body" id="editACBody">
        <!-- Dynamic content -->
    </div>
</div>

<!-- Delete Confirm Popup -->
<div class="popup" id="deletePopup">
    <div class="popup-header">
        <h3><i class="fas fa-exclamation-triangle text-danger"></i> Konfirmasi Hapus</h3>
        <button class="popup-close" onclick="closePopup('deletePopup')">&times;</button>
    </div>
    <div class="popup-body">
        <p>Anda yakin ingin menghapus <strong id="deleteName"></strong>?</p>
        <p class="text-danger text-sm">Semua data AC, Service Order, dan Invoice akan ikut terhapus.</p>
        <div class="popup-actions">
            <button class="btn btn-danger" id="deleteConfirmBtn">
                <i class="fas fa-trash"></i> Hapus
            </button>
            <button class="btn btn-secondary" onclick="closePopup('deletePopup')">Batal</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.PAGE_SYNC_CONFIG = {
    rootSelector: '#dashboardSyncRoot',
    snapshotRoute: '{{ route("dashboard.snapshot") }}',
    afterRender: 'dashboardRealtimeAfterRender',
};
const ROUTES = {
    masjidStore: '{{ route("masjid.store") }}',
    // warga.store temporarily disabled until route defined
    masjidUpdate: (id) => `/masjid/${id}`,
    masjidDestroy: (id) => `/masjid/${id}`,
    masjidDetail: (id) => `/masjid/${id}`,
    acBulk: '{{ route("ac.bulk") }}',
    acUpdate: (id) => `/ac/${id}`,
    acDestroy: (id) => `/ac/${id}`,
};
const isFrontdesk = {{ auth()->user()->isFrontdesk() ? 'true' : 'false' }};

// Manual refresh function for dashboard (replaces auto-sync)
function manualRefreshDashboard() {
    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-sync fa-spin"></i> Memuat...';
    btn.disabled = true;

    // Trigger manual snapshot refresh
    window.refreshCurrentPageSnapshot()
        .then(() => {
            showToast('Data berhasil diperbarui!', 'success');
        })
        .catch((error) => {
            showToast('Gagal memperbarui data: ' + error.message, 'error');
            console.error('Dashboard refresh failed:', error);
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
}
</script>
<script src="{{ asset('js/dashboard.js') }}"></script>
@endpush

