@extends('layouts.app')

@section('title', 'Dashboard - AC Anggota')

@section('content')
@php
    $visibleAnggotas = $anggotas->getCollection();
    $visibleUnits = $visibleAnggotas->sum(fn ($anggota) => $anggota->acUnits->sum('quantity'));
    $activeOrders = $dashboardMetrics['active_orders'] ?? 0;
    $needsAttention = $dashboardMetrics['needs_attention_anggota'] ?? 0;
    $searchTerm = request('search');
@endphp

<div id="dashboardSyncRoot">
<div class="page-container page-operations page-operations--dashboard">
    <section class="ops-hero ops-hero--dashboard glass-surface" data-aos="fade-down">
        <div class="ops-hero__copy">
            <span class="ops-hero__eyebrow">Anggota Operations Center</span>
            <div class="page-header page-header--hero">
                <div>
                    <h1 class="page-title"><i class="fas fa-th-large"></i> Dashboard Anggota</h1>
                    <p class="page-subtitle">Portofolio anggota, urgensi servis, dan kontak operasional dalam satu panel.</p>
                </div>
                <div class="page-actions page-actions--hero">
                    <button class="btn btn-secondary" type="button" onclick="manualRefreshDashboard()">
                        <i class="fas fa-rotate-right"></i> Refresh Data
                    </button>
                    @if(auth()->user()->isFrontdesk())
                    <button class="btn btn-primary" onclick="openPopup('addAnggotaPopup')">
                        <i class="fas fa-plus"></i> Tambah Anggota
                    </button>
                    @endif
                    <a href="{{ route('modules.ac-anggota.monitoring') }}" class="btn btn-outline">
                        <i class="fas fa-wave-square"></i> Buka Monitoring
                    </a>
                </div>
            </div>
            <p class="ops-hero__lead">
                Selamat datang, <strong>{{ auth()->user()->name }}</strong>. Gunakan kartu anggota untuk meninjau kesiapan servis,
                kontak, dan order aktif tanpa berpindah halaman.
            </p>
            <div class="ops-chip-row">
                <span class="ops-chip">
                    <i class="fas fa-user-shield"></i>
                    <strong>{{ ucfirst(auth()->user()->role) }}</strong>
                </span>
                <span class="ops-chip">
                    <i class="fas fa-filter"></i>
                    {{ $searchTerm ? 'Filter aktif: "' . $searchTerm . '"' : 'Mode tampilan: semua anggota' }}
                </span>
                <span class="ops-chip">
                    <i class="fas fa-bell"></i>
                    {{ $needsAttention }} anggota perlu tindak lanjut
                </span>
                <span class="ops-chip">
                    <i class="fas fa-link-slash"></i>
                    {{ $dashboardMetrics['pending_setup_anggota'] ?? 0 }} anggota masih pending setup AC
                </span>
            </div>
        </div>
        <div class="ops-kpi-grid" data-stagger-group>
            <article class="ops-kpi-card" data-stagger-item>
                <span class="ops-kpi-card__label">Total Anggota</span>
                <strong class="ops-kpi-card__value">{{ $dashboardMetrics['total_anggota'] ?? $anggotas->total() }}</strong>
                <span class="ops-kpi-card__meta">Anggota terdaftar</span>
            </article>
            <article class="ops-kpi-card" data-stagger-item>
                <span class="ops-kpi-card__label">Unit Terpantau</span>
                <strong class="ops-kpi-card__value">{{ $dashboardMetrics['total_units'] ?? $visibleUnits }}</strong>
                <span class="ops-kpi-card__meta">Total unit AC lintas seluruh anggota</span>
            </article>
            <article class="ops-kpi-card" data-stagger-item>
                <span class="ops-kpi-card__label">Order Aktif</span>
                <strong class="ops-kpi-card__value">{{ $activeOrders }}</strong>
                <span class="ops-kpi-card__meta">Service order yang masih berjalan</span>
            </article>
            <article class="ops-kpi-card ops-kpi-card--alert" data-stagger-item>
                <span class="ops-kpi-card__label">Perlu Follow-Up</span>
                <strong class="ops-kpi-card__value">{{ $needsAttention }}</strong>
                <span class="ops-kpi-card__meta">Status harus servis atau overdue</span>
            </article>
        </div>
    </section>

    <div class="role-info-bar role-info-bar--elevated">
        <i class="fas fa-circle-info"></i>
        @if(auth()->user()->isManager())
        Anda dapat meninjau kesiapan anggota di sini lalu menyetujui service order dari halaman Monitoring.
        @elseif(auth()->user()->isFrontdesk())
        Anda dapat menambah anggota, mengelola unit AC, dan menyiapkan data sebelum membuat service order.
        @else
        Anda berada dalam mode baca. Semua data anggota dan status servis dapat dipantau tanpa hak ubah.
        @endif
    </div>

    <section class="search-bar ops-control-bar" data-aos="fade-up" data-aos-delay="120">
        <div class="ops-control-bar__header">
            <div>
                <h2 class="ops-section-title">Cari Anggota</h2>
                <p class="ops-section-copy">Telusuri anggota berdasarkan ID atau nama untuk mempercepat eksekusi operasional.</p>
            </div>
            <div class="ops-control-meta">
                <span class="notification-badge notification-badge--neutral">{{ $anggotas->count() }} kartu tampil</span>
                <span class="notification-badge notification-badge--soft">Scroll untuk inspeksi cepat</span>
            </div>
        </div>
        <form action="{{ route('modules.ac-anggota.dashboard') }}" method="GET" class="search-form search-form--hero">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Cari ID atau nama anggota..."
                       value="{{ $searchTerm }}" class="search-input">
                @if($searchTerm)
                    <a href="{{ route('modules.ac-anggota.dashboard') }}" class="search-clear" aria-label="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>
        @if($searchTerm)
            <p class="search-result-info">Menampilkan hasil untuk: <strong>"{{ $searchTerm }}"</strong> ({{ $anggotas->total() }} ditemukan)</p>
        @endif
    </section>

    <div class="cards-grid ops-card-grid" id="anggotaGrid" data-stagger-group style="--cols-xs: 1; --cols-sm: 2;">
        @forelse($anggotas as $anggota)
        @php
            $urgency = $anggota->urgency_status;
            $urgencyLabel = match($urgency) {
                'aman' => 'Aman',
                'harus_servis' => 'Harus Servis',
                'overdue' => 'Overdue',
                default => 'Belum Ada Data',
            };
            $phoneEntries = is_array($anggota->phone_numbers)
                ? array_filter($anggota->phone_numbers)
                : (filled($anggota->phone_numbers) ? [$anggota->phone_numbers] : []);
            $phoneSummary = collect($phoneEntries)->take(2)->implode(' · ');
            $lastServiceText = $anggota->max_days_since_service
                ? $anggota->max_days_since_service . ' hari lalu'
                : 'Belum ada catatan';
            $activeOrder = $anggota->serviceOrders->first(fn ($serviceOrder) => $serviceOrder->isActive());
        @endphp
        <article class="anggota-card ops-masjid-card urgency-{{ $urgency }} ui-reveal" data-id="{{ $anggota->id }}" data-stagger-item>
            <div class="card-accent-bar"></div>
            <div class="card-top">
                <div class="card-top__eyebrow">
                    <span class="card-type-chip">
                        Anggota
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
                    <span class="card-id">{{ $anggota->custom_id }}</span>
                    <span class="card-counter">{{ $anggota->serviceOrders->count() }} order</span>
                </div>
                @if($anggota->setup_status === 'pending_ac')
                <span class="notification-badge notification-badge--warning">
                    <i class="fas fa-link-slash"></i> Pending setup AC
                </span>
                @endif
                <div class="card-name">{{ $anggota->name }}</div>
                <div class="card-address"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($anggota->address, 76) }}</div>
                <div class="anggota-contact-stack">
                    <span class="contact-chip"><i class="fas fa-phone"></i> {{ $phoneSummary ?: 'Tidak ada nomor' }}</span>
                </div>
                <div class="masjid-card-metrics">
                    <div class="masjid-metric">
                        <span class="masjid-metric__label">Unit AC</span>
                        <strong class="masjid-metric__value">{{ $anggota->acUnits->sum('quantity') }}</strong>
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
                <button class="btn btn-sm btn-info" type="button" onclick="showDetail({{ $anggota->id }})">
                    <i class="fas fa-eye"></i> Detail AC
                </button>
                @if(auth()->user()->isFrontdesk())
                <button class="btn btn-sm btn-warning" type="button" onclick="openEditAC({{ $anggota->id }})">
                    <i class="fas fa-tools"></i> Kelola AC
                </button>
                <button class="btn btn-sm btn-secondary" type="button" onclick="openEditAnggota({ id: {{ $anggota->id }}, name: @js($anggota->name), address: @js($anggota->address), phones: @js(array_values($phoneEntries)) })">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-danger" type="button" onclick="confirmDelete({{ $anggota->id }}, @js($anggota->name))">
                    <i class="fas fa-trash"></i>
                </button>
                @endif
            </div>
        </article>
        @empty
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-users"></i></div>
            <h3>Belum Ada Data Anggota</h3>
            <p>{{ $searchTerm ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Mulai dengan menambahkan anggota pertama.' }}</p>
            @if(auth()->user()->isFrontdesk() && !request('search'))
            <button class="btn btn-primary" onclick="openPopup('addAnggotaPopup')">
                <i class="fas fa-plus"></i> Tambah Anggota
            </button>
            @endif
        </div>
        @endforelse
    </div>

    @if(method_exists($anggotas, 'hasPages') && $anggotas->hasPages())
    <div class="pagination-shell pagination-shell--fixed">
        {{ $anggotas->onEachSide(1)->links() }}
    </div>
    @endif
</div>
</div>

<!-- Add Anggota Popup -->
<div class="popup popup-lg" id="addAnggotaPopup">
    <div class="popup-header">
        <h3><i class="fas fa-user-plus"></i> Daftarkan Anggota Baru</h3>
        <button class="popup-close" onclick="closePopup('addAnggotaPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <form id="addAnggotaForm">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tipe <span class="required">*</span></label>
                    <select name="type" class="form-select" required>
                        <option value="individual">Individual</option>
                        <option value="business">Business</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama <span class="required">*</span></label>
                    <input type="text" name="name" class="form-input" placeholder="Nama lengkap..." required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Alamat <span class="required">*</span></label>
                <textarea name="address" class="form-textarea" placeholder="Alamat lengkap..." required rows="2"></textarea>
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
                <button type="button" class="btn btn-secondary" onclick="resetAnggotaForm()">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add AcAnggota Popup -->
<div class="popup popup-lg" id="addAcAnggotaPopup">
    <div class="popup-header">
        <h3><i class="fas fa-snowflake"></i> Tambah Data AC</h3>
        <button class="popup-close" onclick="closePopup('addAcAnggotaPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <div class="info-banner">
            <i class="fas fa-info-circle"></i>
            <span id="acAnggotaMeta">Anggota berhasil didaftarkan.</span> Sekarang tambahkan data AC.
        </div>
        <input type="hidden" id="acAnggotaId">
        <div id="acUnitsList">
            <!-- AC units will be added dynamically -->
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="addACUnit()">
            <i class="fas fa-plus"></i> Tambah Unit AC
        </button>
        <div class="popup-actions" style="margin-top: 1rem">
            <button type="button" class="btn btn-primary" onclick="saveAcAnggotaUnits()">
                <i class="fas fa-save"></i> Konfirmasi
            </button>
            <button type="button" class="btn btn-secondary" onclick="skipAcAnggotaSetup()">
                Lewati
            </button>
        </div>
    </div>
</div>

<!-- Detail AcAnggota Popup -->
<div class="popup popup-lg" id="detailAcAnggotaPopup">
    <div class="popup-header">
        <h3><i class="fas fa-list"></i> Detail Unit AC</h3>
        <button class="popup-close" onclick="closePopup('detailAcAnggotaPopup')">&times;</button>
    </div>
    <div class="popup-body" id="detailAcBody">
        <!-- Dynamic content -->
    </div>
</div>

<!-- Edit Anggota Popup -->
<div class="popup popup-lg" id="editAnggotaPopup">
    <div class="popup-header">
        <h3><i class="fas fa-edit"></i> Edit Data Anggota</h3>
        <button class="popup-close" onclick="closePopup('editAnggotaPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <form id="editAnggotaForm">
            <input type="hidden" id="editAnggotaId">
            <div class="form-group">
                <label class="form-label">Nama</label>
                <input type="text" id="editAnggotaName" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea id="editAnggotaAddress" class="form-textarea" rows="2" required></textarea>
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
                <button type="button" class="btn btn-secondary" onclick="closePopup('editAnggotaPopup')">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit AcAnggota Popup -->
<div class="popup popup-lg" id="editAcAnggotaPopup">
    <div class="popup-header">
        <h3><i class="fas fa-tools"></i> Kelola Unit AC</h3>
        <button class="popup-close" onclick="closePopup('editAcAnggotaPopup')">&times;</button>
    </div>
    <div class="popup-body" id="editAcBody">
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
        <p class="text-danger text-sm">Semua data AC dan Service Order akan ikut terhapus.</p>
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
snapshotRoute: '{{ route("modules.ac-anggota.dashboard.snapshot") }}',
    afterRender: 'dashboardRealtimeAfterRender',
};
const ROUTES = {
    anggotaStore: '/modules/ac-anggota/anggota',
    anggotaUpdate: (id) => `/modules/ac-anggota/anggota/${id}`,
    anggotaDestroy: (id) => `/modules/ac-anggota/anggota/${id}`,
    anggotaDetail: (id) => `/modules/ac-anggota/anggota/${id}`,
    acBulk: '/modules/ac-anggota/ac/bulk',
    acUpdate: (id) => `/modules/ac-anggota/ac/${id}`,
    acDestroy: (id) => `/modules/ac-anggota/ac/${id}`,
};
const isFrontdesk = {{ auth()->user()->isFrontdesk() ? 'true' : 'false' }};

// Manual refresh function
function manualRefreshDashboard() {
    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-sync fa-spin"></i> Memuat...';
    btn.disabled = true;

    window.refreshCurrentPageSnapshot()
        .then(() => showToast('Data berhasil diperbarui!', 'success'))
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
