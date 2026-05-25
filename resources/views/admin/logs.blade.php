@extends('layouts.app')

@section('title', 'Admin Log Dashboard - AC Servis Masjid')

@section('content')
<div class="page-container">
    <section class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-clipboard-list"></i> Admin Log Dashboard</h1>
            <p class="page-subtitle">Pantau audit trail sinkronisasi dan workflow lintas semua user.</p>
        </div>
    </section>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-icon bg-primary"><i class="fas fa-satellite-dish"></i></div>
            <div class="summary-content">
                <div class="summary-kicker" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);font-weight:600;margin-bottom:2px">Hari Ini</div>
                <div class="summary-num">{{ $todaySyncCount }}</div>
                <div class="summary-label">Sync Events</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon bg-info"><i class="fas fa-route"></i></div>
            <div class="summary-content">
                <div class="summary-kicker" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);font-weight:600;margin-bottom:2px">Hari Ini</div>
                <div class="summary-num">{{ $todayWorkflowCount }}</div>
                <div class="summary-label">Workflow Steps</div>
            </div>
        </div>
    </div>

    <section class="search-bar">
        <form method="GET" class="search-form">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="search" class="search-input" placeholder="Cari actor, role, status, order number..." value="{{ $search }}">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
            @if($search !== '')
                <a href="{{ route('admin.logs.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </section>

    <div class="glass-card" style="margin-bottom:24px">
        <div class="card-header" style="padding:0 0 16px;border-bottom:1px solid var(--border);margin-bottom:0">
            <div>
                <h3 style="font-size:1rem;font-weight:600;display:flex;align-items:center;gap:8px">
                    <i class="fas fa-broadcast-tower" style="color:var(--primary)"></i>
                    Realtime Sync Events
                </h3>
                <p style="font-size:0.8125rem;color:var(--text-muted);margin-top:4px">Jejak event yang dibroadcast untuk silent refresh lintas session.</p>
            </div>
        </div>
        <div class="table-container" style="box-shadow:none;border:none;margin-bottom:0;margin-top:16px">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Tipe</th>
                        <th>Resource</th>
                        <th>Actor</th>
                        <th>Role</th>
                        <th>ID Resource</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($syncEvents as $event)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div style="width:8px;height:8px;border-radius:50%;background:var(--primary);flex-shrink:0"></div>
                                    <span style="font-size:0.8125rem;white-space:nowrap">{{ optional($event->created_at)->format('d M Y H:i') }}</span>
                                </div>
                            </td>
                            <td><span class="detail-chip">{{ $event->type }}</span></td>
                            <td style="font-weight:500">{{ $event->resource }}</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div style="width:28px;height:28px;border-radius:50%;background:var(--primary-soft);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.6875rem;flex-shrink:0">
                                        {{ $event->actor_name ? strtoupper(substr($event->actor_name, 0, 1)) : '?' }}
                                    </div>
                                    <span style="font-weight:500">{{ $event->actor_name ?: '-' }}</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $roleColors = ['frontdesk' => 'primary', 'manager' => 'success', 'admin' => 'danger', 'technician' => 'warning', 'viewer' => 'info'];
                                    $roleLabels = ['frontdesk' => 'Front Desk', 'manager' => 'Manager', 'admin' => 'Admin', 'technician' => 'Teknisi', 'viewer' => 'Viewer'];
                                    $evtRole = $event->actor_role ?: '';
                                @endphp
                                @if($evtRole && isset($roleLabels[$evtRole]))
                                    <span class="role-badge role-{{ $evtRole }}">{{ $roleLabels[$evtRole] }}</span>
                                @else
                                    <span class="text-muted text-sm">{{ $evtRole ?: '-' }}</span>
                                @endif
                            </td>
                            <td><code style="font-size:0.75rem;background:var(--gray-50);padding:2px 6px;border-radius:4px">{{ $event->resource_id ?: '-' }}</code></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fas fa-satellite-dish"></i></div>
                                    <h3>Belum Ada Sync Event</h3>
                                    <p>Event sinkronisasi akan muncul saat ada aksi dari user.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($syncEvents->hasPages())
        <div class="pagination-wrap" style="padding:16px 0 0">
            {{ $syncEvents->links() }}
        </div>
        @endif
    </div>

    <div class="glass-card">
        <div class="card-header" style="padding:0 0 16px;border-bottom:1px solid var(--border);margin-bottom:0">
            <div>
                <h3 style="font-size:1rem;font-weight:600;display:flex;align-items:center;gap:8px">
                    <i class="fas fa-tasks" style="color:var(--success)"></i>
                    Workflow Audit Steps
                </h3>
                <p style="font-size:0.8125rem;color:var(--text-muted);margin-top:4px">Riwayat langkah kerja service order per actor.</p>
            </div>
        </div>
        <div class="table-container" style="box-shadow:none;border:none;margin-bottom:0;margin-top:16px">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Order</th>
                        <th>Lokasi</th>
                        <th>Step</th>
                        <th>Actor</th>
                        <th>Role</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workflowSteps as $step)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div style="width:8px;height:8px;border-radius:50%;background:var(--success);flex-shrink:0"></div>
                                    <span style="font-size:0.8125rem;white-space:nowrap">{{ optional($step->created_at)->format('d M Y H:i') }}</span>
                                </div>
                            </td>
                            <td>
                                @if($step->serviceOrder)
                                    <span class="order-num">{{ $step->serviceOrder->order_number }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td style="max-width:180px">
                                <span style="font-size:0.8125rem" title="{{ optional(optional($step->serviceOrder)->masjid)->name }}">
                                    {{ Str::limit(optional(optional($step->serviceOrder)->masjid)->name, 24) ?: '-' }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $stepLabel = \App\Models\WorkflowStep::stepLabel($step->step);
                                @endphp
                                <span class="status-badge status-approved">{{ $stepLabel }}</span>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div style="width:28px;height:28px;border-radius:50%;background:var(--success-bg);color:var(--success);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.6875rem;flex-shrink:0">
                                        {{ $step->actor_name ? strtoupper(substr($step->actor_name, 0, 1)) : '?' }}
                                    </div>
                                    <span style="font-weight:500">{{ $step->actor_name ?: '-' }}</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $wfRole = $step->actor_role ?: '';
                                @endphp
                                @if($wfRole && isset($roleLabels[$wfRole]))
                                    <span class="role-badge role-{{ $wfRole }}">{{ $roleLabels[$wfRole] }}</span>
                                @else
                                    <span class="text-muted text-sm">{{ $wfRole ?: '-' }}</span>
                                @endif
                            </td>
                            <td style="max-width:200px">
                                <span style="font-size:0.8125rem;color:var(--text-muted)" title="{{ $step->notes }}">
                                    {{ Str::limit($step->notes, 30) ?: '-' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fas fa-tasks"></i></div>
                                    <h3>Belum Ada Log Workflow</h3>
                                    <p>Langkah workflow akan tercatat saat service order diproses.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($workflowSteps->hasPages())
        <div class="pagination-wrap" style="padding:16px 0 0">
            {{ $workflowSteps->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
