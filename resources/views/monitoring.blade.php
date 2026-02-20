@extends('layouts.app')

@section('title', 'Monitoring - AC Servis Masjid')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-chart-line"></i> Monitoring</h1>
            <p class="page-subtitle">Pantau status servis AC seluruh masjid</p>
        </div>
        <div class="page-actions">
            @if(auth()->user()->isFrontdesk())
            <button class="btn btn-primary" onclick="openPopup('serviceOrderPopup')">
                <i class="fas fa-plus"></i> Buat Service Order
            </button>
            @endif
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-icon bg-primary">
                <i class="fas fa-mosque"></i>
            </div>
            <div class="summary-content">
                <div class="summary-num">{{ $totalLokasi }}</div>
                <div class="summary-label">Total Lokasi</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon bg-info">
                <i class="fas fa-snowflake"></i>
            </div>
            <div class="summary-content">
                <div class="summary-num">{{ $totalUnit }}</div>
                <div class="summary-label">Total Unit AC</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon bg-danger">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="summary-content">
                <div class="summary-num">{{ $overdue }}</div>
                <div class="summary-label">Overdue (>120 hari)</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon bg-warning">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="summary-content">
                <div class="summary-num">{{ $orders->where('status', 'pending')->count() }}</div>
                <div class="summary-label">Order Pending</div>
            </div>
        </div>
    </div>

    <!-- Urgency Legend -->
    <div class="legend-bar">
        <span class="legend-item"><span class="legend-dot urgency-aman"></span> Aman (&lt;90 hari)</span>
        <span class="legend-item"><span class="legend-dot urgency-harus_servis"></span> Harus Servis (90–120 hari)</span>
        <span class="legend-item"><span class="legend-dot urgency-overdue"></span> Overdue (&gt;120 hari)</span>
    </div>

    <!-- Search & Filter -->
    <div class="search-bar">
        <form action="{{ route('monitoring') }}" method="GET" class="search-form">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Cari order / masjid..." 
                       value="{{ request('search') }}" class="search-input">
            </div>
            <select name="status" class="form-select" style="width:auto">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->anyFilled(['search', 'status']))
                <a href="{{ route('monitoring') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    <!-- Orders Table -->
    @if($orders->count() > 0)
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No. Order</th>
                    <th>Masjid</th>
                    <th>Tgl Servis</th>
                    <th>Detail Unit</th>
                    <th>Status</th>
                    <th>Urgensi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td>
                        <div class="order-num">{{ $order->order_number }}</div>
                        <div class="text-sm text-muted">{{ $order->created_at->format('d M Y') }}</div>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $order->masjid->name }}</div>
                        <div class="text-sm text-muted">{{ $order->masjid->custom_id }}</div>
                    </td>
                    <td>
                        <div>{{ $order->service_date->format('d M Y') }}</div>
                        <div class="text-sm {{ $order->service_date < now() ? 'text-danger' : 'text-success' }}">
                            {{ $order->service_date < now() ? 'Lewat' : 'Mendatang' }}
                        </div>
                    </td>
                    <td>
                        @foreach($order->serviceDetails as $detail)
                        <div class="detail-chip">{{ $detail->pk_type }} {{ $detail->brand }} × {{ $detail->quantity }}</div>
                        @endforeach
                    </td>
                    <td>
                        <span class="status-badge status-{{ $order->status }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td>
                        @php $urgency = $order->masjid->urgency_status; @endphp
                        <span class="urgency-badge urgency-text-{{ $urgency }}">
                            {{ $urgency === 'aman' ? 'Aman' : ($urgency === 'harus_servis' ? 'Harus Servis' : 'Overdue') }}
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-sm btn-info" onclick="showOrderDetail({{ $order->id }})">
                                <i class="fas fa-eye"></i>
                            </button>

                            @if(auth()->user()->isManager())
                                @if($order->status === 'pending')
                                <button class="btn btn-sm btn-success" onclick="approveOrder({{ $order->id }})">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                @elseif($order->status === 'approved')
                                <button class="btn btn-sm btn-warning" onclick="cancelApprove({{ $order->id }})">
                                    <i class="fas fa-undo"></i> Batal
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteOrder({{ $order->id }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            @endif

                            @if($order->status === 'approved')
                            <a href="{{ route('spk.print', $order->id) }}" target="_blank" class="btn btn-sm btn-secondary">
                                <i class="fas fa-print"></i> SPK
                            </a>
                            <a href="{{ route('invoice.print', $order->id) }}" target="_blank" class="btn btn-sm btn-primary">
                                <i class="fas fa-file-invoice"></i> Invoice
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>Tidak Ada Service Order</h3>
        <p>{{ request()->anyFilled(['search', 'status']) ? 'Tidak ada hasil untuk filter tersebut.' : 'Belum ada service order yang dibuat.' }}</p>
    </div>
    @endif

    <!-- Masjid Urgency Overview -->
    <div class="section-title" style="margin-top: 2rem">
        <h2>Status Urgensi Seluruh Masjid</h2>
    </div>
    <div class="urgency-grid">
        @foreach($masjids as $masjid)
        <div class="urgency-card urgency-card-{{ $masjid->urgency_status }}">
            <div class="urgency-card-header">
                <span class="urgency-card-id">{{ $masjid->custom_id }}</span>
                <span class="urgency-dot urgency-{{ $masjid->urgency_status }}"></span>
            </div>
            <div class="urgency-card-name">{{ Str::limit($masjid->name, 30) }}</div>
            <div class="urgency-card-info">
                <span>{{ $masjid->acUnits->sum('quantity') }} unit</span>
                <span>{{ $masjid->max_days_since_service }} hari</span>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Service Order Popup -->
@if(auth()->user()->isFrontdesk())
<div class="popup popup-xl" id="serviceOrderPopup">
    <div class="popup-header">
        <h3><i class="fas fa-clipboard-plus"></i> Buat Service Order</h3>
        <button class="popup-close" onclick="closePopup('serviceOrderPopup')">&times;</button>
    </div>
    <div class="popup-body popup-two-col">
        <!-- Left: Masjid List -->
        <div class="popup-col-left">
            <h4>Pilih Masjid</h4>
            <div class="search-input-wrap" style="margin-bottom: 0.75rem">
                <i class="fas fa-search"></i>
                <input type="text" id="soMasjidSearch" class="search-input" placeholder="Cari masjid...">
            </div>
            <div class="masjid-select-list" id="masjidSelectList">
                @foreach($masjids as $m)
                <div class="masjid-select-item" 
                     data-id="{{ $m->id }}"
                     data-name="{{ $m->name }}"
                     data-address="{{ $m->address }}"
                     data-dkm="{{ $m->dkm_name }}"
                     data-marbot="{{ $m->marbot_name }}"
                     data-phone="{{ json_encode($m->phone_numbers) }}"
                     data-ac="{{ json_encode($m->acUnits) }}"
                     data-type="{{ $m->type }}"
                     onclick="selectMasjidForSO(this)">
                    <div class="msi-id">{{ $m->custom_id }}</div>
                    <div class="msi-name">{{ $m->name }}</div>
                    <div class="msi-units">{{ $m->acUnits->sum('quantity') }} unit AC</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Right: Order Form -->
        <div class="popup-col-right">
            <div id="soFormContent" style="display:none">
                <h4 id="soMasjidName"></h4>
                <p id="soMasjidAddress" class="text-muted text-sm"></p>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Ditemui oleh</label>
                        <select id="soMeetingPerson" class="form-select">
                            <option value="dkm">DKM (<span id="soDkmName"></span>)</option>
                            <option value="marbot">Marbot (<span id="soMarbotName"></span>)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor HP</label>
                        <input type="text" id="soPhone" class="form-input" placeholder="Nomor HP...">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Rincian Unit Servis</label>
                    <div id="soDetailsList"></div>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addSODetail()">
                        <i class="fas fa-plus"></i> Tambah Unit
                    </button>
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Rencana Servis</label>
                    <input type="date" id="soServiceDate" class="form-input" min="{{ date('Y-m-d') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Instruksi Tambahan</label>
                    <textarea id="soNotes" class="form-textarea" rows="2" placeholder="Catatan tambahan..."></textarea>
                </div>

                <!-- Info Harga -->
                <div id="soHargaInfo" class="info-banner" style="display:none;margin-top:0.5rem;font-size:0.78rem"></div>

                <!-- Total Estimasi -->
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0.875rem;background:var(--primary-soft);border:1px solid var(--primary-mid);border-radius:var(--radius-sm);margin-top:0.5rem;font-size:0.82rem;color:var(--primary);font-weight:600">
                    <span><i class="fas fa-receipt" style="margin-right:0.4rem"></i> Estimasi Total</span>
                    <span id="soTotalPreview">–</span>
                </div>

                <div class="popup-actions">
                    <button class="btn btn-secondary btn-sm" onclick="showOrderHistory()">
                        <i class="fas fa-history"></i> History
                    </button>
                    <button class="btn btn-primary" onclick="submitServiceOrder()">
                        <i class="fas fa-paper-plane"></i> Kirim Order
                    </button>
                </div>
            </div>
            <div id="soEmptyState" class="empty-state">
                <i class="fas fa-hand-pointer"></i>
                <p>Pilih masjid dari daftar kiri</p>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Order Detail Popup -->
<div class="popup popup-lg" id="orderDetailPopup">
    <div class="popup-header">
        <h3><i class="fas fa-clipboard-list"></i> Detail Service Order</h3>
        <button class="popup-close" onclick="closePopup('orderDetailPopup')">&times;</button>
    </div>
    <div class="popup-body" id="orderDetailBody">
        <!-- Dynamic -->
    </div>
</div>

<!-- History Popup -->
<div class="popup popup-lg" id="historyPopup">
    <div class="popup-header">
        <h3><i class="fas fa-history"></i> Riwayat Service Order</h3>
        <button class="popup-close" onclick="closePopup('historyPopup')">&times;</button>
    </div>
    <div class="popup-body" id="historyBody"></div>
</div>


<!-- Popup Konfirmasi Ganti Order Lama -->
<div class="popup" id="replaceConfirmPopup" style="max-width:480px;z-index:500">
    <div class="popup-header">
        <h3><i class="fas fa-exclamation-triangle" style="color:var(--warning)"></i> &nbsp;Order Aktif Sudah Ada!</h3>
        <button class="popup-close" onclick="closePopup('replaceConfirmPopup')">&times;</button>
    </div>
    <div class="popup-body">

        {{-- Info order lama --}}
        <div style="background:var(--warning-soft);border:1.5px solid var(--warning);border-radius:var(--radius);padding:1rem;margin-bottom:1.1rem">
            <div style="font-size:0.78rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.6rem">
                <i class="fas fa-clipboard-list"></i> &nbsp;Order yang sudah ada:
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.3rem 0;border-bottom:1px solid rgba(0,0,0,0.06)">
                <span style="font-size:0.82rem;color:#92400e">No. Order</span>
                <strong class="order-num" id="rcOrderNumber" style="color:var(--primary)"></strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.3rem 0;border-bottom:1px solid rgba(0,0,0,0.06)">
                <span style="font-size:0.82rem;color:#92400e">Status</span>
                <strong id="rcStatus"></strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.3rem 0">
                <span style="font-size:0.82rem;color:#92400e">Tgl. Servis</span>
                <span style="font-size:0.82rem;font-weight:600" id="rcServiceDate"></span>
            </div>
        </div>

        <p style="font-size:0.875rem;color:var(--text-muted);margin-bottom:1.35rem;line-height:1.6">
            Masjid ini sudah punya service order aktif. Apakah ingin
            <strong style="color:var(--danger)">menghapus order lama</strong>
            dan menggantinya dengan order baru yang baru saja kamu buat?
        </p>

        {{-- Tombol --}}
        <div style="display:flex;flex-direction:column;gap:0.6rem">
            <button class="btn btn-danger"
                    style="width:100%;justify-content:center;padding:0.75rem;font-size:0.95rem;font-weight:700"
                    onclick="confirmReplaceOrder()">
                <i class="fas fa-sync-alt"></i> &nbsp;Ya, Hapus Order Lama &amp; Buat Baru
            </button>
            <button class="btn btn-secondary"
                    style="width:100%;justify-content:center;padding:0.65rem;font-size:0.875rem"
                    onclick="cancelReplaceOrder()">
                <i class="fas fa-arrow-left"></i> &nbsp;Tidak, Kembali &amp; Biarkan Order Lama
            </button>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
const ROUTES_MON = {
    soStore: '{{ route("service-order.store") }}',
    soApprove: (id) => `/service-order/${id}/approve`,
    soCancel: (id) => `/service-order/${id}/cancel-approve`,
    soDelete: (id) => `/service-order/${id}`,
    soDeleteMgr: (id) => `/service-order/${id}/manager`,
    soHistory: (id) => `/masjid/${id}/history`,
    spk: (id) => `/service-order/${id}/spk`,
    invoice: (id) => `/service-order/${id}/invoice`,
};
const isManager = {{ auth()->user()->isManager() ? 'true' : 'false' }};
const isFrontdesk2 = {{ auth()->user()->isFrontdesk() ? 'true' : 'false' }};
</script>
<script src="{{ asset('js/monitoring.js') }}"></script>
@endpush
