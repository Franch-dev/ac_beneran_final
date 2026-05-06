@extends('layouts.app')

@section('title', 'Monitoring - AC Servis Masjid')

@section('content')
@php
    $statusLabels = \App\Models\ServiceOrder::STATUS_LABELS;
    $pendingCount = (int) ($statusTotals['pending'] ?? 0);
    $waitingInvoiceCount = (int) ($statusTotals['waiting_invoice'] ?? 0);
    $waitingReviewCount = (int) ($statusTotals['waiting_review'] ?? 0);
    $searchTerm = request('search');
    $statusFilter = request('status');
    $currentUser = auth()->user();
    $canCreateSpkInvoice = static function ($order) use ($currentUser): bool {
        $status = strtolower(trim((string) ($order->status ?? '')));
        $latestStep = strtolower(trim((string) optional($order->latestWorkflowStep)->step));
        $hasAllowedRole = $currentUser && (
            $currentUser->isFrontdesk()
            || $currentUser->isManager()
            || $currentUser->isAdmin()
        );

        if (! $hasAllowedRole || $order->invoice) {
            return false;
        }

        if (in_array($status, ['completed', 'closed', 'cancelled', 'selesai'], true)) {
            return false;
        }

        $spkAlreadyStartedSteps = [
            'spk_invoice_created',
            'spk_invoice_approved',
            'assigned',
            'in_progress',
            'technician_reported',
            'invoice_edited',
            'payment_received',
            'printed',
            'completed',
            'cancelled',
        ];

        return in_array($status, ['pending', 'approved', 'waiting_invoice'], true)
            && ! in_array($latestStep, $spkAlreadyStartedSteps, true);
    };
@endphp
<div id="monitoringSyncRoot">
<div class="page-container page-operations page-operations--monitoring">
    <section class="ops-hero ops-hero--monitoring glass-surface" data-aos="fade-down">
        <div class="ops-hero__copy">
            <span class="ops-hero__eyebrow">Workflow Control Tower</span>
            <div class="page-header page-header--hero">
                <div>
                    <h1 class="page-title"><i class="fas fa-chart-line"></i> Monitoring Service Order</h1>
                    <p class="page-subtitle">Pantau antrean servis, bottleneck workflow, dan urgensi lokasi secara real-time.</p>
                </div>
                <div class="page-actions page-actions--hero">
                    <button class="btn btn-secondary" type="button" onclick="manualRefreshMonitoring()">
                        <i class="fas fa-rotate-right"></i> Refresh Data
                    </button>
                    @if(auth()->user()->isFrontdesk() || auth()->user()->isAdmin())
                    <button class="btn btn-primary" onclick="openPopup('serviceOrderPopup')">
                        <i class="fas fa-plus"></i> Buat Service Order
                    </button>
                    @endif
                    <a href="{{ route('dashboard') }}" class="btn btn-outline">
                        <i class="fas fa-th-large"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
            <p class="ops-hero__lead">
                Gunakan filter, status badge, dan timeline audit untuk memastikan setiap service order bergerak
                tanpa kehilangan konteks lokasi, teknisi, dan dokumen pendukung.
            </p>
            <div class="ops-chip-row">
                <span class="ops-chip"><i class="fas fa-list-check"></i> {{ $orders->total() }} order terindeks</span>
                <span class="ops-chip"><i class="fas fa-filter"></i> {{ $statusFilter ? 'Status: ' . ($statusLabels[$statusFilter] ?? $statusFilter) : 'Semua status' }}</span>
                <span class="ops-chip"><i class="fas fa-magnifying-glass"></i> {{ $searchTerm ? 'Cari: "' . $searchTerm . '"' : 'Pencarian nonaktif' }}</span>
            </div>
        </div>
        <div class="ops-kpi-grid" data-stagger-group>
            <article class="ops-kpi-card" data-stagger-item>
                <span class="ops-kpi-card__label">Lokasi Dipantau</span>
                <strong class="ops-kpi-card__value">{{ $totalLokasi }}</strong>
                <span class="ops-kpi-card__meta">Masjid dan musholla aktif</span>
            </article>
            <article class="ops-kpi-card" data-stagger-item>
                <span class="ops-kpi-card__label">Unit AC</span>
                <strong class="ops-kpi-card__value">{{ $totalUnit }}</strong>
                <span class="ops-kpi-card__meta">Total perangkat dalam inventori</span>
            </article>
            <article class="ops-kpi-card" data-stagger-item>
                <span class="ops-kpi-card__label">Overdue</span>
                <strong class="ops-kpi-card__value">{{ $overdue }}</strong>
                <span class="ops-kpi-card__meta">Lokasi dengan jeda servis di atas 120 hari</span>
            </article>
            <article class="ops-kpi-card ops-kpi-card--alert" data-stagger-item>
                <span class="ops-kpi-card__label">Pending Saat Ini</span>
                <strong class="ops-kpi-card__value">{{ $pendingCount }}</strong>
                <span class="ops-kpi-card__meta">Order pending lintas seluruh antrean</span>
            </article>
        </div>
    </section>

    <div class="summary-grid ops-summary-grid" data-stagger-group>
        <div class="summary-card summary-card--primary ui-reveal" data-stagger-item>
            <div class="summary-icon bg-primary">
                <i class="fas fa-mosque"></i>
            </div>
            <div class="summary-content">
                <div class="summary-kicker">Cakupan</div>
                <div class="summary-num counter" data-target="{{ $totalLokasi }}">0</div>
                <div class="summary-label">Total Lokasi</div>
                <div class="summary-caption">Seluruh lokasi yang sedang dipantau</div>
            </div>
        </div>

        <div class="summary-card summary-card--info ui-reveal" data-stagger-item>
            <div class="summary-icon bg-info">
                <i class="fas fa-snowflake"></i>
            </div>
            <div class="summary-content">
                <div class="summary-kicker">Inventori</div>
                <div class="summary-num counter" data-target="{{ $totalUnit }}">0</div>
                <div class="summary-label">Total Unit AC</div>
                <div class="summary-caption">Basis beban kerja teknisi dan estimator</div>
            </div>
        </div>

        <div class="summary-card summary-card--danger ui-reveal" data-stagger-item>
            <div class="summary-icon bg-danger">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="summary-content">
                <div class="summary-kicker">Prioritas</div>
                <div class="summary-num counter" data-target="{{ $overdue }}">0</div>
                <div class="summary-label">Overdue</div>
                <div class="summary-caption">Perlu penjadwalan ulang atau eskalasi</div>
            </div>
        </div>

        <div class="summary-card summary-card--warning ui-reveal" data-stagger-item>
            <div class="summary-icon bg-warning">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="summary-content">
                <div class="summary-kicker">Queue</div>
                <div class="summary-num counter" data-target="{{ $pendingCount }}">0</div>
                <div class="summary-label">Order Pending</div>
                <div class="summary-caption">Butuh approval atau pemrosesan lanjutan</div>
            </div>
        </div>
    </div>

    <section class="search-bar ops-control-bar" data-aos="fade-up" data-aos-delay="120">
        <div class="ops-control-bar__header">
            <div>
                <h2 class="ops-section-title">Filter Antrean</h2>
                <p class="ops-section-copy">Gabungkan pencarian teks dan filter status untuk menelusuri order, SLA, dan potensi hambatan proses.</p>
            </div>
            <div class="ops-control-meta">
                <span class="notification-badge notification-badge--warning">{{ $pendingCount }} pending</span>
                <span class="notification-badge notification-badge--info">{{ $waitingInvoiceCount }} menunggu invoice</span>
                <span class="notification-badge notification-badge--accent">{{ $waitingReviewCount }} menunggu review</span>
            </div>
        </div>
        <form action="{{ route('monitoring') }}" method="GET" class="search-form search-form--hero">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Cari order / masjid..."
                       value="{{ $searchTerm }}" class="search-input">
            </div>
            <select name="status" class="form-select ops-select-filter">
                <option value="">Semua Status</option>
                @foreach($statusLabels as $key => $label)
                <option value="{{ $key }}" {{ $statusFilter == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->anyFilled(['search', 'status']))
                <a href="{{ route('monitoring') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </section>

    <!-- Orders Table -->
    @if($orders->count() > 0)
    <section class="monitoring-table-card ui-reveal" data-aos="fade-up" data-aos-delay="160">
        <div class="table-card-header">
            <div>
                <h2>Queue Service Order</h2>
                <p>Tabel ini dirancang untuk review cepat, approval, penugasan teknisi, dan pelacakan dokumen dari satu tempat.</p>
            </div>
            <div class="table-chip-wrap">
                <span class="table-chip"><i class="fas fa-layer-group"></i> {{ $orders->count() }} order di halaman ini</span>
                <span class="table-chip"><i class="fas fa-bolt"></i> {{ $pendingCount }} butuh tindakan cepat</span>
                <span class="table-chip"><i class="fas fa-file-invoice"></i> {{ $waitingInvoiceCount + $waitingReviewCount }} butuh dokumen</span>
            </div>
        </div>
        <div class="table-container ops-table-shell">
        <table class="data-table monitoring-table ops-data-table">
            <thead>
                <tr>
                    <th>No. Order</th>
                    <th>Masjid</th>
                    <th>Tgl Servis</th>
                    <th>Detail Unit</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Urgensi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                @php
                    $latestStep = $order->latestWorkflowStep;
                    $assignment = $order->technicianAssignment;
                    $masjid = $order->masjid;
                    $masjidId = optional($masjid)->id;
                    $masjidName = optional($masjid)->name ?? '-';
                    $masjidCustomId = optional($masjid)->custom_id ?? '-';
                    $masjidUnitCount = $masjid?->acUnits?->sum('quantity') ?? 0;
                    $urgency = optional($masjid)->urgency_status;
                    $urgencyLabel = match($urgency) {
                        'aman' => 'Aman',
                        'harus_servis' => 'Harus Servis',
                        'overdue' => 'Overdue',
                        default => 'Belum Ada Data',
                    };
                    $progress = match($order->status) {
                        'pending' => ['value' => 18, 'label' => 'Menunggu approval', 'tone' => 'warning'],
                        'waiting_invoice' => ['value' => 35, 'label' => 'Menunggu SPK & Invoice', 'tone' => 'info'],
                        'approved' => ['value' => 50, 'label' => 'Siap Ditugaskan', 'tone' => 'success'],
                        'in_progress' => ['value' => 75, 'label' => 'Teknisi sedang bekerja', 'tone' => 'primary'],
                        'waiting_review' => ['value' => 90, 'label' => 'Menunggu review akhir', 'tone' => 'accent'],
                        'completed' => ['value' => 100, 'label' => 'Workflow selesai', 'tone' => 'success'],
                        default => ['value' => 12, 'label' => 'Status belum dipetakan', 'tone' => 'neutral'],
                    };
                @endphp
                <tr>
                    <td>
                        <div class="table-primary">
                            <div class="order-num">{{ $order->order_number }}</div>
                            <div class="table-meta">Dibuat {{ $order->created_at->format('d M Y') }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="location-cell">
                            <div class="location-name" style="cursor:pointer;color:var(--primary);" onclick="showMasjidSideDetail(@json($masjidId))">
                                {{ $masjidName }}
                            </div>
                            @if($masjid)
                            <div class="location-meta">{{ $order->masjid->custom_id }} · {{ $order->masjid->acUnits->sum('quantity') }} unit</div>
                            @else
                            <div class="location-meta">{{ $masjidCustomId }} &middot; {{ $masjidUnitCount }} unit</div>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="date-chip {{ $order->service_date < now() ? 'is-late' : '' }}">
                            <strong>{{ $order->service_date->format('d M Y') }}</strong>
                            <span class="table-meta">{{ $order->service_date < now() ? 'Lewat jadwal' : 'Rencana mendatang' }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="detail-chip-stack">
                        @foreach($order->serviceDetails as $detail)
                        <div class="detail-chip">{{ $detail->pk_type }} {{ $detail->brand }} × {{ $detail->quantity }}</div>
                        @endforeach
                        </div>
                    </td>
                    <td>
                        <span class="status-badge status-{{ $order->status }}">
                            <span class="badge-dot"></span>
                            {{ $statusLabels[$order->status] ?? \App\Models\ServiceOrder::statusLabel($order->status) }}
                        </span>
                    </td>
                    <td>
                        @if($latestStep)
                            <div class="workflow-summary">
                                <span class="detail-chip detail-chip--stage">
                                    {{ \App\Models\WorkflowStep::stepLabel($latestStep->step) }}
                                </span>
                                @if($assignment)
                                <div class="workflow-summary__actor">
                                    <i class="fas fa-user-hard-hat"></i>
                                    {{ $assignment->technician_name }}
                                </div>
                                @endif
                            </div>
                        @else
                            <span class="text-muted text-sm">–</span>
                        @endif
                        <div class="progress-stack">
                            <div class="progress-track">
                                <span class="progress-fill tone-{{ $progress['tone'] }}" style="--progress-value: {{ $progress['value'] }}%"></span>
                            </div>
                            <div class="progress-meta">
                                {{ $progress['label'] }}
                            </div>
                        </div>
                    </td>
                    <td>
                        @php
                            $urgency = optional($masjid)->urgency_status;
                            $urgencyLabel = match($urgency) {
                                'aman' => 'Aman',
                                'harus_servis' => 'Harus Servis',
                                'overdue' => 'Overdue',
                                default => 'Belum Ada Data',
                            };
                        @endphp
                        <span class="urgency-badge urgency-badge-{{ $urgency }}">
                            <span class="badge-dot"></span>
                            {{ $urgencyLabel }}
                        </span>
                    </td>
                    <td class="table-cell-actions">
                        <div class="action-btns action-btns--dense">
                            <button class="btn btn-sm btn-info" type="button" onclick='showOrderDetail(@json($order->id), @json($order->order_number), @json($masjidName), @json($order->service_date->format('d M Y')))'>
                                <i class="fas fa-eye"></i> Detail
                            </button>

                            {{-- Assign technician after SPK --}}
                            @if((auth()->user()->isManager() || auth()->user()->isAdmin()) && $order->status === 'approved')
                            <button class="btn btn-sm btn-outline btn-accent" type="button"
                                onclick='openAssignTech(@json($order->id), @json($order->order_number), @json($masjidName))'>
                                <i class="fas fa-user-hard-hat"></i> Tugaskan
                            </button>
                            @endif

                            {{-- Technician mark task done / submit field report --}}
                            @if(auth()->user()->isTechnician() && in_array($order->status, ['approved','in_progress']))
                            <button class="btn btn-sm btn-warning" type="button" onclick='openFieldReport(@json($order->id), @json($order->order_number))'>
                                <i class="fas fa-clipboard-check"></i> Submit Laporan
                            </button>
                            @endif

                            {{-- Manager/Frontdesk/Admin generate SPK & Invoice --}}
                            @if($canCreateSpkInvoice($order))
                            <button class="btn btn-sm btn-primary" type="button" onclick="createSpkInvoice({{ $order->id }})">
                                <i class="fas fa-file-invoice"></i> Buat SPK & Invoice
                            </button>
                            @endif



                            {{-- Order Selesai button for 'completed' status with dual confirmation --}}
                            @if($order->status == 'completed')
                                {{-- Frontdesk/Admin confirmation --}}
                                @if((auth()->user()->isFrontdesk() || auth()->user()->isAdmin()) && !$order->frontdesk_confirmed_complete)
                                <button class="btn btn-sm btn-success" type="button" onclick="openDualConfirmation({{ $order->id }}, 'frontdesk', 'Apakah Anda (Frontdesk) menyetujui bahwa service order ini sudah selesai?')" title="Konfirmasi Selesai">
                                    <i class="fas fa-check"></i> Konfirmasi Selesai
                                </button>
                                @endif

                                {{-- Manager/Admin confirmation --}}
                                @if((auth()->user()->isManager() || auth()->user()->isAdmin()) && !$order->manager_confirmed_complete)
                                <button class="btn btn-sm btn-success" type="button" onclick="openDualConfirmation({{ $order->id }}, 'manager', 'Apakah Anda (Manager) menyetujui bahwa service order ini sudah selesai?')" title="Konfirmasi Selesai">
                                    <i class="fas fa-check"></i> Konfirmasi Selesai
                                </button>
                                @endif

                                {{-- Show if both confirmed --}}
                                @if($order->frontdesk_confirmed_complete && $order->manager_confirmed_complete)
                                <span class="btn btn-sm btn-success" style="opacity:0.7">
                                    <i class="fas fa-check-double"></i> Selesai
                                </span>
                                @endif
                            @endif



                            {{-- Manager/Admin approve SPK & Invoice --}}
                            @if((auth()->user()->isManager() || auth()->user()->isAdmin()) && $order->status === 'waiting_review')
                            <button class="btn btn-sm btn-success" type="button" onclick="approveInvoice({{ $order->id }})">
                                <i class="fas fa-check-circle"></i> Approve SPK & Invoice
                            </button>
                            @endif

                            {{-- Manager: Approve Additional Fee if field report submitted --}}
                            @if((auth()->user()->isManager() || auth()->user()->isAdmin()) && $order->field_report_additional_fee > 0 && !$order->manager_approved_additional_fee)
                            <button class="btn btn-sm btn-warning" type="button" onclick="approveAdditionalFee({{ $order->id }})">
                                <i class="fas fa-coins"></i> Approve Biaya Extra
                            </button>
                            @endif

                            {{-- Frontdesk/Admin delete order --}}

                            @if(auth()->user()->isFrontdesk() || auth()->user()->isAdmin())
                            <button class="btn btn-xs btn-danger btn-icon btn-delete-small" type="button" onclick="deleteServiceOrder({{ $order->id }})" title="Hapus Order" aria-label="Hapus order">
                                <i class="fas fa-trash"></i>
                            </button>
                            @endif



                            {{-- SPK / Invoice viewer --}}
                            @if(in_array($order->status, ['approved','in_progress','waiting_invoice','waiting_review','completed']))
                                <a href="{{ route('spk.print', $order->id) }}" target="_blank" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-print"></i> SPK
                                </a>
                            @endif
                            @if($order->invoice)
                                <a href="{{ route('invoice.print', $order->id) }}" target="_blank" class="btn btn-sm btn-primary">
                                    <i class="fas fa-file-invoice"></i> Invoice
                                </a>
                            @endif

                            {{-- Workflow timeline button (all manager/admin/frontdesk/tech) --}}
                            <button class="btn btn-sm btn-info" type="button" onclick='showWorkflowTimeline(@json($order->id), @json($order->order_number), @json($masjidName))'>
                                <i class="fas fa-stream"></i> Timeline
                            </button>

                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="monitoring-mobile-list">
        @foreach($orders as $order)
        @php
            $latestStep = $order->latestWorkflowStep;
            $assignment = $order->technicianAssignment;
            $masjid = $order->masjid;
            $masjidId = optional($masjid)->id;
            $masjidName = optional($masjid)->name ?? '-';
            $masjidCustomId = optional($masjid)->custom_id ?? '-';
            $urgency = optional($masjid)->urgency_status;
            $urgencyLabel = match($urgency) {
                'aman' => 'Aman',
                'harus_servis' => 'Harus Servis',
                'overdue' => 'Overdue',
                default => 'Belum Ada Data',
            };
            $scheduleState = $order->service_date < now() ? 'Lewat' : 'Mendatang';
        @endphp
        <article class="monitoring-mobile-card" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 5) * 50 }}">
            <div class="monitoring-mobile-card__header">
                <div>
                    <div class="order-num">{{ $order->order_number }}</div>
                    <div class="monitoring-mobile-card__site" style="cursor:pointer;color:var(--primary);" onclick="showMasjidSideDetail(@json($masjidId))">{{ $masjidName }}</div>
                </div>
                <span class="status-badge status-{{ $order->status }}">
                    {{ $statusLabels[$order->status] ?? \App\Models\ServiceOrder::statusLabel($order->status) }}
                </span>
            </div>
            <div class="monitoring-mobile-card__meta">
                <span>{{ $masjidCustomId }}</span>
                <span class="notification-badge {{ $order->service_date < now() ? 'notification-badge--danger' : 'notification-badge--success' }}">{{ $scheduleState }}</span>
                <span class="notification-badge notification-badge--neutral">{{ $urgencyLabel }}</span>
            </div>
            <div class="monitoring-mobile-card__grid">
                <div>
                    <span class="monitoring-mobile-card__label">Tanggal Servis</span>
                    <strong>{{ $order->service_date->format('d M Y') }}</strong>
                </div>
                <div>
                    <span class="monitoring-mobile-card__label">Workflow</span>
                    <strong>{{ $latestStep ? \App\Models\WorkflowStep::stepLabel($latestStep->step) : '-' }}</strong>
                </div>
                <div>
                    <span class="monitoring-mobile-card__label">Teknisi</span>
                    <strong>{{ $assignment ? $assignment->technician_name : '-' }}</strong>
                </div>
                <div>
                    <span class="monitoring-mobile-card__label">Unit</span>
                    <strong>{{ $order->serviceDetails->sum('quantity') }}</strong>
                </div>
            </div>
            <div class="detail-chip-list">
                @foreach($order->serviceDetails as $detail)
                <div class="detail-chip">{{ $detail->pk_type }} {{ $detail->brand }} x {{ $detail->quantity }}</div>
                @endforeach
            </div>
            <div class="action-btns">
                <button class="btn btn-sm btn-info" type="button" onclick='showOrderDetail(@json($order->id), @json($order->order_number), @json($masjidName), @json($order->service_date->format('d M Y')))'>
                    <i class="fas fa-eye"></i> Detail
                </button>

                @if((auth()->user()->isManager() || auth()->user()->isAdmin()) && $order->status === 'approved')
                <button class="btn btn-sm btn-outline btn-accent" type="button"
                    onclick='openAssignTech(@json($order->id), @json($order->order_number), @json($masjidName))'>
                    <i class="fas fa-user-hard-hat"></i> Tugaskan
                </button>
                @endif

                @if(auth()->user()->isTechnician() && in_array($order->status, ['approved','in_progress']))
                <button class="btn btn-sm btn-warning" type="button" onclick="markTaskDone({{ $order->id }})">
                    <i class="fas fa-check-double"></i> Task Done
                </button>
                @endif

                @if($canCreateSpkInvoice($order))
                <button class="btn btn-sm btn-primary" type="button" onclick="createSpkInvoice({{ $order->id }})">
                    <i class="fas fa-file-invoice"></i> Buat SPK & Invoice
                </button>
                @endif

                @if((auth()->user()->isManager() || auth()->user()->isAdmin()) && $order->status === 'waiting_review')
                <button class="btn btn-sm btn-success" type="button" onclick="approveInvoice({{ $order->id }})">
                    <i class="fas fa-check-circle"></i> Approve SPK & Invoice
                </button>
                @endif

                @if(auth()->user()->isFrontdesk() || auth()->user()->isAdmin())
                <button class="btn btn-sm btn-danger" type="button" onclick="deleteServiceOrder({{ $order->id }})">
                    <i class="fas fa-trash"></i> Hapus
                </button>
                @endif

                @if(in_array($order->status, ['approved','in_progress','waiting_invoice','waiting_review','completed']))
                <a href="{{ route('spk.print', $order->id) }}" target="_blank" class="btn btn-sm btn-secondary">
                    <i class="fas fa-print"></i> SPK
                </a>
                @endif
                @if($order->invoice)
                <a href="{{ route('invoice.print', $order->id) }}" target="_blank" class="btn btn-sm btn-primary">
                    <i class="fas fa-file-invoice"></i> Invoice
                </a>
                @endif

                <button class="btn btn-sm btn-info" type="button"
                    onclick='showWorkflowTimeline(@json($order->id), @json($order->order_number), @json($masjidName))'>
                    <i class="fas fa-stream"></i> Timeline
                </button>
            </div>
        </article>
        @endforeach
    </div>
    </section>
    @else
    <div class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>Tidak Ada Service Order</h3>
        <p>{{ request()->anyFilled(['search', 'status']) ? 'Tidak ada hasil untuk filter tersebut.' : 'Belum ada service order yang dibuat.' }}</p>
        @if(auth()->user()->isFrontdesk() || auth()->user()->isAdmin())
        <button class="btn btn-primary" type="button" onclick="openPopup('serviceOrderPopup')">
            <i class="fas fa-plus"></i> Buat Service Order
        </button>
        @endif
    </div>
    @endif

    @if($orders->hasPages())
    <div class="pagination-shell pagination-shell--fixed">
        {{ $orders->onEachSide(1)->links() }}
    </div>
    @endif

        <!-- Urgency Legend -->
    <div class="legend-bar">
        <span class="legend-item"><span class="legend-dot urgency-aman"></span> Aman (<90 hari)</span>
        <span class="legend-item"><span class="legend-dot urgency-harus_servis"></span> Harus Servis (90-120 hari)</span>
        <span class="legend-item"><span class="legend-dot urgency-overdue"></span> Overdue (>120 hari)</span>
    </div>

    <!-- Masjid Urgency Overview -->
    <div class="section-title ops-section-heading" style="margin-top: 2rem">
        <h2>Status Urgensi Seluruh Masjid</h2>
        <p class="ops-section-copy">Snapshot lokasi yang perlu dijadwalkan lebih cepat berdasarkan jeda servis terpanjang.</p>
    </div>
    <div class="urgency-grid" data-stagger-group>
        @foreach($masjids as $masjid)
        <div class="urgency-card urgency-card-{{ $masjid->urgency_status }} ui-reveal" data-stagger-item>
            <div class="urgency-card-header">
                <span class="urgency-card-id">{{ $masjid->custom_id }}</span>
                <span class="urgency-dot urgency-{{ $masjid->urgency_status }}"></span>
            </div>
            <div class="urgency-card-name">{{ Str::limit($masjid->name, 30) }}</div>
            <div class="urgency-card-info">
                <span>{{ $masjid->acUnits->sum('quantity') }} unit</span>
                <span>{{ $masjid->max_days_since_service ? $masjid->max_days_since_service . ' hari' : '-' }}</span>
            </div>
        </div>
        @endforeach
    </div>

    @if($masjids->hasPages())
    <div class="pagination-shell">
        {{ $masjids->onEachSide(1)->links() }}
    </div>
    @endif
</div>
</div>

<!-- Service Order Popup -->
@if(auth()->user()->isFrontdesk() || auth()->user()->isAdmin())
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

                {{-- Row 1: Masjid Info Header --}}
                <div class="so-header-info">
                    <h4 id="soMasjidName"></h4>
                    <p id="soMasjidAddress" class="text-muted text-sm"></p>
                </div>

                {{-- Row 2: PK Selector Card (clickable PK type badges) --}}
                <div class="so-pk-selector-card">
                    <div class="so-pk-selector-label">
                        <i class="fas fa-snowflake"></i> Pilih Unit AC — klik untuk menambahkan
                    </div>
                    <div class="so-pk-badges" id="soPkBadges"></div>
                    <div class="so-ac-summary" id="soAcSummary"></div>
                </div>

                {{-- Row 3: Detail Row Groups (PK-type groupings) --}}
                <div class="so-detail-groups" id="soDetailGroups"></div>

                {{-- Row 4: Contact info + Tanggal --}}
                <div class="form-row" style="margin-top:0.75rem">
                    <div class="form-group">
                        <label class="form-label">Ditemui oleh</label>
                        <select id="soMeetingPerson" class="form-select">
                            <option value="dkm">DKM</option>
                            <option value="marbot">Marbot</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor HP</label>
                        <input type="text" id="soPhone" class="form-input" placeholder="Nomor HP...">
                    </div>
                </div>

                <div class="form-group" style="margin-top:0.5rem">
                    <label class="form-label">Tanggal Rencana Servis</label>
                    <input type="date" id="soServiceDate" class="form-input" min="{{ date('Y-m-d') }}">
                </div>

                <div class="form-group" style="margin-top:0.5rem">
                    <label class="form-label">Instruksi Tambahan</label>
                    <textarea id="soNotes" class="form-textarea" rows="2" placeholder="Catatan tambahan..."></textarea>
                </div>

                {{-- Row 5: Pricing Preview --}}
                <div class="so-price-preview">
                    <div class="so-price-items" id="soPriceItems"></div>
                    <div class="so-price-total">
                        <span><i class="fas fa-receipt"></i> Estimasi Total</span>
                        <span id="soTotalPreview" class="so-total-amount">Rp 0</span>
                    </div>
                </div>

                {{-- Row 6: Action Buttons (single bar) --}}
                <div class="popup-actions so-action-bar">
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
        <button class="popup-close" type="button" onclick="closePopup('orderDetailPopup')" aria-label="Tutup detail service order">&times;</button>
    </div>
    <div class="popup-body" id="orderDetailBody">
        <div style="text-align:center;padding:2rem;color:var(--text-muted);">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p style="margin-top:1rem;">Memuat data...</p>
        </div>
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

<!-- Assign Technician Popup -->
<div class="popup popup-lg" id="assignTechPopup">
    <div class="popup-header">
        <h3><i class="fas fa-user-hard-hat"></i> Tugaskan Teknisi</h3>
        <button class="popup-close" onclick="closePopup('assignTechPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <input type="hidden" id="assignTechOrderId">
        <div class="info-banner">
            <i class="fas fa-info-circle"></i>
            <span id="assignTechOrderInfo">Order: -</span>
        </div>
        <div class="form-group" style="margin-top:1rem;">
            <label class="form-label">Pilih Teknisi <span class="required">*</span></label>
            <select id="technicianSelect" class="form-select">
                <option value="">Memuat daftar teknisi...</option>
            </select>
        </div>
        <div class="form-group" style="margin-top:0.75rem;">
            <label class="form-label">Catatan untuk teknisi</label>
            <textarea id="assignTechNotes" class="form-textarea" rows="3" placeholder="Instruksi tambahan..."></textarea>
        </div>
        <div class="popup-actions">
            <button class="btn btn-primary" onclick="submitAssignTech()">
                <i class="fas fa-paper-plane"></i> Tugaskan
            </button>
            <button class="btn btn-secondary" onclick="closePopup('assignTechPopup')">
                Batal
            </button>
        </div>
    </div>
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

<!-- Account Manager Confirmation Modal -->
<div class="popup confirm-modal" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
    <div class="popup-header">
        <h3 id="confirmModalTitle">
            <span class="confirm-icon" id="confirmModalIcon" aria-hidden="true">
                <i class="fas fa-check-circle"></i>
            </span>
            <span id="confirmModalHeading">Konfirmasi Aksi</span>
        </h3>
        {{-- Tombol close dihapus untuk manager --}}
        @if(!auth()->user()->isManager())
        <button class="popup-close" onclick="closeConfirmModal()" aria-label="Tutup modal">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
        @endif
    </div>
    <div class="popup-body">
        <p class="confirm-message" id="confirmModalMessage">
            Apakah Anda yakin ingin melakukan aksi ini?
        </p>
        <div class="confirm-details" id="confirmModalDetails" style="display: none;">
            <div class="confirm-details-row">
                <span class="confirm-details-label">No. Order</span>
                <span class="confirm-details-value" id="confirmDetailOrder">-</span>
            </div>
            <div class="confirm-details-row">
                <span class="confirm-details-label">Masjid</span>
                <span class="confirm-details-value" id="confirmDetailMasjid">-</span>
            </div>
            <div class="confirm-details-row">
                <span class="confirm-details-label">Tanggal Servis</span>
                <span class="confirm-details-value" id="confirmDetailDate">-</span>
            </div>
        </div>
        <div class="confirm-actions">
            <button class="btn btn-success" id="confirmModalConfirmBtn" onclick="executeConfirmAction()">
                <i class="fas fa-check" aria-hidden="true"></i> Ya, Lanjutkan
            </button>
            <button class="btn btn-secondary" onclick="closeConfirmModal()">
                <i class="fas fa-times" aria-hidden="true"></i> Batal
            </button>
        </div>
    </div>
</div>

@include('monitoring.workflow_panel')

<!-- Field Report Popup (Technician) -->
<div class="popup popup-lg" id="fieldReportPopup">
    <div class="popup-header">
        <h3><i class="fas fa-clipboard-check"></i> Laporan Pekerjaan Lapangan</h3>
        <button class="popup-close" onclick="closePopup('fieldReportPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <form id="fieldReportForm">
            <input type="hidden" id="fieldReportOrderId">

            <div class="form-group">
                <label class="form-label">Laporan Pekerjaan <span class="required">*</span></label>
                <textarea id="fieldReportNotes" class="form-textarea" rows="4" placeholder="Jelaskan pekerjaan yang dilakukan di lapangan..." required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Biaya Tambahan (Rp)</label>
                <input type="number" id="fieldReportAdditionalFee" class="form-input" placeholder="0" min="0" value="0">
                <small class="text-muted">Isi jika ada biaya ekstra (misal: perbaikan leak freon, ganti sparepart, dll)</small>
            </div>

            <div class="form-group">
                <label class="form-label">Alat/Bahan Tambahan</label>
                <div id="toolsMaterialsList">
                    <div class="tools-material-row">
                        <input type="text" name="tm_name[]" class="form-input" placeholder="Nama alat/bahan">
                        <input type="number" name="tm_quantity[]" class="form-input" placeholder="Qty" min="1" value="1">
                        <input type="number" name="tm_price[]" class="form-input" placeholder="Harga">
                        <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.tools-material-row').remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline" onclick="addToolMaterialRow()">
                    <i class="fas fa-plus"></i> Tambah Alat/Bahan
                </button>
            </div>

            <div class="popup-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Kirim Laporan
                </button>
                <button type="button" class="btn btn-secondary" onclick="closePopup('fieldReportPopup')">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Additional Fee Approval Popup (Manager) -->
<div class="popup popup-lg" id="additionalFeeApprovalPopup">
    <div class="popup-header">
        <h3><i class="fas fa-coins"></i> Persetujuan Biaya Tambahan</h3>
        <button class="popup-close" onclick="closePopup('additionalFeeApprovalPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <div id="additionalFeeInfo" class="info-banner"></div>

        <div class="form-group">
            <label class="form-label">Catatan Persetujuan</label>
            <textarea id="approvalNotes" class="form-textarea" rows="2" placeholder="Catatan opsional..."></textarea>
        </div>

        <div class="popup-actions">
            <button class="btn btn-success" onclick="confirmAdditionalFee()">
                <i class="fas fa-check"></i> Setuju & Update Invoice
            </button>
            <button class="btn btn-secondary" onclick="closePopup('additionalFeeApprovalPopup')">
                Batal
            </button>
        </div>
    </div>
</div>

<!-- Dual Confirmation Popup -->
<div class="popup" id="dualConfirmPopup">
    <div class="popup-header">
        <h3><i class="fas fa-check-double"></i> Konfirmasi Order Selesai</h3>
        <button class="popup-close" onclick="closePopup('dualConfirmPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <p id="dualConfirmMessage"></p>
        <div class="popup-actions">
            <button class="btn btn-primary" onclick="submitDualConfirmation()">
                <i class="fas fa-check"></i> Konfirmasi
            </button>
            <button class="btn btn-secondary" onclick="closePopup('dualConfirmPopup')">
                Batal
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.PAGE_SYNC_CONFIG = {
    rootSelector: '#monitoringSyncRoot',
    snapshotRoute: '{{ route("monitoring.snapshot") }}',
    persistentSelectors: ['#serviceOrderPopup'],
};
window.ROUTES_MON = {
    soStore: '{{ route("service-order.store") }}',
    soApprove: (id) => `/service-order/${id}/approve`,
    soCancel: (id) => `/service-order/${id}/cancel-approve`,
    soDelete: (id) => `/service-order/${id}`,
    soDeleteMgr: (id) => `/service-order/${id}/manager`,
    soHistory: (id) => `/masjid/${id}/history`,
    spk: (id) => `/service-order/${id}/spk`,
    invoice: (id) => `/service-order/${id}/invoice`,
    workflowBase: "{{ url('/workflow') }}",
    workflowTechnicians: "{{ route('workflow.technicians') }}",
};
const ROUTES_MON = window.ROUTES_MON;
window.generateInvoice = window.generateInvoice || function () {
    if (typeof window.showToast === 'function') {
        window.showToast('Fitur invoice masih dimuat. Coba lagi.', 'warning');
        return;
    }

    console.warn('generateInvoice handler is not loaded yet.');
};
const isManager = {{ auth()->user()->isManager() ? 'true' : 'false' }};
const isFrontdesk2 = {{ auth()->user()->isFrontdesk() ? 'true' : 'false' }};
window.HARGA_CONFIG = {
    MASJID: { '1PK': 150000, '2PK': 200000, '5PK': 350000 },
    MUSHOLLA: { '1PK': 120000, '2PK': 170000, '5PK': 300000 },
};
</script>
@vite(['resources/js/monitoring.js'])
@endpush
