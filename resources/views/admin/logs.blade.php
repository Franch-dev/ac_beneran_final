@extends('layouts.app')

@section('title', 'Admin Log Dashboard - AC Servis Masjid')

@section('content')
<div class="page-container page-operations">
    <section class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-clipboard-list"></i> Admin Log Dashboard</h1>
            <p class="page-subtitle">Pantau audit trail sinkronisasi dan workflow lintas semua user.</p>
        </div>
    </section>

    <div class="summary-grid">
        <div class="summary-card summary-card--primary">
            <div class="summary-icon bg-primary"><i class="fas fa-satellite-dish"></i></div>
            <div class="summary-content">
                <div class="summary-kicker">Hari Ini</div>
                <div class="summary-num">{{ $todaySyncCount }}</div>
                <div class="summary-label">Sync Events</div>
            </div>
        </div>
        <div class="summary-card summary-card--info">
            <div class="summary-icon bg-info"><i class="fas fa-route"></i></div>
            <div class="summary-content">
                <div class="summary-kicker">Hari Ini</div>
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

    <section class="monitoring-table-card">
        <div class="table-card-header">
            <div>
                <h2>Realtime Sync Events</h2>
                <p>Jejak event yang dibroadcast untuk silent refresh lintas session.</p>
            </div>
        </div>
        <div class="table-container">
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
                            <td>{{ optional($event->created_at)->format('d M Y H:i:s') }}</td>
                            <td>{{ $event->type }}</td>
                            <td>{{ $event->resource }}</td>
                            <td>{{ $event->actor_name ?: '-' }}</td>
                            <td>{{ $event->actor_role ?: '-' }}</td>
                            <td>{{ $event->resource_id ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada sync event.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">
            {{ $syncEvents->links() }}
        </div>
    </section>

    <section class="monitoring-table-card">
        <div class="table-card-header">
            <div>
                <h2>Workflow Audit Steps</h2>
                <p>Riwayat langkah kerja service order per actor.</p>
            </div>
        </div>
        <div class="table-container">
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
                            <td>{{ optional($step->created_at)->format('d M Y H:i:s') }}</td>
                            <td>{{ $step->serviceOrder->order_number ?? '-' }}</td>
                            <td>{{ optional(optional($step->serviceOrder)->masjid)->name ?? '-' }}</td>
                            <td>{{ \App\Models\WorkflowStep::stepLabel($step->step) }}</td>
                            <td>{{ $step->actor_name ?: '-' }}</td>
                            <td>{{ $step->actor_role ?: '-' }}</td>
                            <td>{{ $step->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Belum ada log workflow.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">
            {{ $workflowSteps->links() }}
        </div>
    </section>
</div>
@endsection
