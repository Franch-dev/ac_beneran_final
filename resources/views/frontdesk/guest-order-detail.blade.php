@extends('layouts.app')

@section('title', 'Review Guest Order - ' . $order->guest_name)

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <a href="{{ route('frontdesk.guest-orders') }}" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Guest Orders
            </a>
            <h1 class="page-title"><i class="fas fa-clipboard-check"></i> Review Guest Order</h1>
            <p class="page-subtitle">Periksa data dari guest, cross-check dengan database, lalu approve atau reject.</p>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger glass-card" style="margin-bottom: 16px;">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <div class="form-row" style="grid-template-columns: 1fr 1fr; margin-bottom: 24px;">
        <!-- Left: Guest Data -->
        <div class="card glass-card">
            <div class="card-header">
                <h3><i class="fas fa-user-edit"></i> Data dari Guest</h3>
            </div>
            <div class="card-body">
                <dl class="detail-list">
                    <div class="detail-row">
                        <dt>Nama</dt>
                        <dd style="font-weight: 600;">{{ $order->guest_name }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt>Telepon</dt>
                        <dd>
                            {{ $order->guest_phone }}
                            @if($order->additional_phone_description)
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                                    <i class="fas fa-info-circle"></i> {{ $order->additional_phone_description }}
                                </div>
                            @endif
                        </dd>
                    </div>
                    <div class="detail-row">
                        <dt>Masjid/Musholla</dt>
                        <dd>{{ $order->masjid?->name ?? $order->masjid_name }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt>Alamat</dt>
                        <dd>{{ $order->address ?? '-' }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt>AC</dt>
                        <dd>{{ $order->ac_amount }} unit {{ $order->ac_type }} ({{ $order->brand ?? '-' }})</dd>
                    </div>
                    <div class="detail-row">
                        <dt>Masalah</dt>
                        <dd>{{ $order->problem_description }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt>Tanggal Submit</dt>
                        <dd>{{ $order->created_at->format('d M Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Right: Masjid DB Data + Validation -->
        <div class="card glass-card">
            <div class="card-header">
                <h3><i class="fas fa-database"></i> Data dari Database</h3>
            </div>
            <div class="card-body">
                @if($order->masjid)
                    <dl class="detail-list">
                        <div class="detail-row">
                            <dt>Nama (DB)</dt>
                            <dd style="font-weight: 600;">{{ $order->masjid->name }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Telepon (DB)</dt>
                            <dd>
                                @if(is_array($order->masjid->phone_numbers))
                                    {{ implode(', ', $order->masjid->phone_numbers) }}
                                @else
                                    {{ $order->masjid->phone_numbers ?? '-' }}
                                @endif
                            </dd>
                        </div>
                        <div class="detail-row">
                            <dt>Alamat (DB)</dt>
                            <dd>{{ $order->masjid->address ?? '-' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>DKM</dt>
                            <dd>{{ $order->masjid->dkm_name ?? '-' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Marbot</dt>
                            <dd>{{ $order->masjid->marbot_name ?? '-' }}</dd>
                        </div>
                    </dl>
                @else
                    <div class="empty-state" style="padding: 32px 20px;">
                        <div class="empty-icon" style="background: var(--warning-bg); color: var(--warning);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3>Masjid tidak ditemukan</h3>
                        <p>Guest mungkin mengetik nama baru. Frontdesk perlu verifikasi manual.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Validation Checklist -->
    <div class="card glass-card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3><i class="fas fa-tasks"></i> Checklist Validasi</h3>
        </div>
        <div class="card-body">
            <div class="validation-checklist">
                <!-- Phone Check -->
                <div class="validation-card {{ $phoneMatch ? 'validation-success' : 'validation-danger' }}">
                    <div class="validation-icon">
                        @if($phoneMatch)
                            <i class="fas fa-check-circle"></i>
                        @else
                            <i class="fas fa-times-circle"></i>
                        @endif
                    </div>
                    <div class="validation-content">
                        <h4>{{ $phoneMatch ? 'Telepon Cocok' : 'Telepon Tidak Cocok' }}</h4>
                        <p>Guest: {{ $order->guest_phone }}</p>
                        <p>DB: {{ is_array($order->masjid?->phone_numbers) ? implode(', ', $order->masjid->phone_numbers) : ($order->masjid?->phone_numbers ?? 'N/A') }}</p>
                    </div>
                </div>

                <!-- Address Check -->
                <div class="validation-card {{ $addressMatch ? 'validation-success' : ($order->masjid ? 'validation-warning' : 'validation-muted') }}">
                    <div class="validation-icon">
                        @if($addressMatch)
                            <i class="fas fa-check-circle"></i>
                        @elseif($order->masjid)
                            <i class="fas fa-exclamation-circle"></i>
                        @else
                            <i class="fas fa-question-circle"></i>
                        @endif
                    </div>
                    <div class="validation-content">
                        <h4>{{ $addressMatch ? 'Alamat Cocok' : ($order->masjid ? 'Alamat Berbeda' : 'Tidak Ada DB') }}</h4>
                        <p>Guest: {{ $order->address ?? '-' }}</p>
                        <p>DB: {{ $order->masjid?->address ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Masjid Exists Check -->
                <div class="validation-card {{ $order->masjid ? 'validation-success' : 'validation-warning' }}">
                    <div class="validation-icon">
                        @if($order->masjid)
                            <i class="fas fa-check-circle"></i>
                        @else
                            <i class="fas fa-exclamation-circle"></i>
                        @endif
                    </div>
                    <div class="validation-content">
                        <h4>{{ $order->masjid ? 'Masjid Ditemukan' : 'Masjid Baru' }}</h4>
                        @if($order->masjid)
                            <p>ID: {{ $order->masjid->id }} • {{ $order->masjid->type }}</p>
                        @else
                            <p>Guest mengetik nama: {{ $order->masjid_name }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Form with Edit -->
    @if($order->status === 'pending_review')
    <div class="card glass-card">
        <div class="card-header">
            <h3><i class="fas fa-edit"></i> Edit & Approve</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('frontdesk.guest-orders.approve', $order->id) }}" method="POST"
                data-confirm="Guest order akan dibuat menjadi service order."
                data-confirm-heading="Approve guest order?"
                data-confirm-type="success"
                data-confirm-text="Ya, Approve">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Kontak *</label>
                        <input type="text" name="guest_name" value="{{ old('guest_name', $order->guest_name) }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telepon Guest *</label>
                        <input type="text" name="guest_phone" value="{{ old('guest_phone', $order->guest_phone) }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Meeting Person *</label>
                        <select name="meeting_person" required class="form-select">
                            <option value="dkm" {{ old('meeting_person', $order->masjid?->dkm_name ? 'dkm' : 'marbot') == 'dkm' ? 'selected' : '' }}>DKM ({{ $order->masjid?->dkm_name ?? '-' }})</option>
                            <option value="marbot" {{ old('meeting_person', $order->masjid?->marbot_name ? 'marbot' : '') == 'marbot' ? 'selected' : '' }}>Marbot ({{ $order->masjid?->marbot_name ?? '-' }})</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telepon yang Dihubungi *</label>
                        <input type="text" name="phone" value="{{ old('phone', $order->guest_phone) }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Service *</label>
                        <input type="date" name="service_date" value="{{ old('service_date', now()->addDays(3)->format('Y-m-d')) }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" rows="3" class="form-textarea" placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="popup-actions" style="margin-top: 24px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve & Buat Service Order
                    </button>
                    <button type="button" onclick="showRejectForm()" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </div>
            </form>

            <!-- Reject Form (hidden by default) -->
            <div id="rejectFormWrap" style="display: none; margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border);">
                <h4 style="margin-bottom: 16px; color: var(--danger); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-ban"></i> Tolak Order
                </h4>
                <form action="{{ route('frontdesk.guest-orders.reject', $order->id) }}" method="POST"
                    data-confirm="Guest order akan ditolak. Pastikan alasan penolakan sudah diisi."
                    data-confirm-heading="Tolak guest order?"
                    data-confirm-type="danger"
                    data-confirm-text="Ya, Tolak">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Alasan Penolakan *</label>
                        <textarea name="rejection_reason" rows="3" required class="form-textarea" placeholder="Jelaskan alasan penolakan..." style="min-height: 100px;"></textarea>
                    </div>
                    <div class="popup-actions">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times"></i> Konfirmasi Tolak
                        </button>
                        <a href="https://wa.me/{{ preg_replace('/^0/', '62', $order->guest_phone) }}?text={{ urlencode('Mohon maaf, order service AC untuk ' . ($order->masjid?->name ?? $order->masjid_name) . ' tidak dapat kami proses saat ini.') }}" target="_blank" class="btn btn-outline" style="color: #25D366; border-color: #25D366;">
                            <i class="fab fa-whatsapp"></i> Kirim via WhatsApp
                        </a>
                        <button type="button" onclick="hideRejectForm()" class="btn btn-secondary">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
.detail-list { display: flex; flex-direction: column; gap: 12px; }
.detail-row { display: flex; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--border); }
.detail-row:last-child { border-bottom: none; }
.detail-row dt { color: var(--text-muted); font-size: 0.8125rem; width: 40%; flex-shrink: 0; }
.detail-row dd { font-size: 0.875rem; margin: 0; flex: 1; }

.validation-checklist { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
.validation-card { display: flex; gap: 16px; padding: 16px; border-radius: var(--radius); }
.validation-success { background: var(--success-bg); }
.validation-warning { background: var(--warning-bg); }
.validation-danger { background: var(--danger-bg); }
.validation-muted { background: var(--gray-50); }
.validation-icon { font-size: 1.5rem; flex-shrink: 0; }
.validation-success .validation-icon { color: var(--success); }
.validation-warning .validation-icon { color: var(--warning); }
.validation-danger .validation-icon { color: var(--danger); }
.validation-muted .validation-icon { color: var(--text-muted); }
.validation-content h4 { font-size: 0.875rem; font-weight: 600; margin-bottom: 6px; }
.validation-content p { font-size: 0.8125rem; margin: 2px 0; }

@media (max-width: 768px) {
    .form-row { grid-template-columns: 1fr !important; }
    .validation-checklist { grid-template-columns: 1fr; }
    .detail-row { flex-direction: column; gap: 4px; }
    .detail-row dt { width: 100%; }
}
</style>

<script>
    function showRejectForm() {
        document.getElementById('rejectFormWrap').style.display = 'block';
    }
    function hideRejectForm() {
        document.getElementById('rejectFormWrap').style.display = 'none';
    }
</script>
@endsection
