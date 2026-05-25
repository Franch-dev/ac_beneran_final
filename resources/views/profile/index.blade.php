@extends('layouts.app')

@section('title', 'Profil - AC Servis Masjid')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-user-circle"></i> Profil Saya</h1>
            <p class="page-subtitle">{{ $user->roleLabel() }} • {{ $user->email }}</p>
        </div>
    </div>

    <div class="glass-card" style="max-width:640px">
        <div style="text-align:center;padding:32px 24px 24px;background:linear-gradient(135deg,var(--primary),var(--primary-hover));color:#fff;border-radius:var(--radius-lg) var(--radius-lg) 0 0;margin:-1.5rem -1.5rem 24px">
            <div style="width:96px;height:96px;margin:0 auto 16px;border-radius:50%;background:rgba(255,255,255,0.2);backdrop-filter:blur(10px);color:#fff;font-size:2.5rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:3px solid rgba(255,255,255,0.3)">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div style="font-size:1.5rem;font-weight:700;margin-bottom:4px">{{ $user->name }}</div>
            <div style="display:inline-flex;align-items:center;gap:6px;padding:4px 14px;border-radius:var(--radius-full);background:rgba(255,255,255,0.15);font-size:0.8125rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em">
                <i class="fas fa-shield-halved"></i>
                {{ $user->roleLabel() }}
            </div>
        </div>

        <div style="font-weight:600;margin-bottom:16px;font-size:1.0625rem;color:var(--text);display:flex;align-items:center;gap:8px">
            <i class="fas fa-id-card" style="color:var(--primary)"></i>
            Informasi Akun
        </div>

        <form id="profileForm">
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ $user->name }}" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ $user->email }}" class="form-input">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <div class="form-input" style="background:var(--gray-50);cursor:default;color:var(--text-muted)">{{ $user->roleLabel() }}</div>
            </div>
            <div class="popup-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>

        <div style="border-top:1px solid var(--border);margin:24px 0"></div>

        <div style="font-weight:600;margin-bottom:16px;font-size:1.0625rem;color:var(--text);display:flex;align-items:center;gap:8px">
            <i class="fas fa-key" style="color:var(--warning)"></i>
            Ubah Password
        </div>

        <form id="passwordForm">
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="form-group">
                <label class="form-label">Password Saat Ini <span class="required">*</span></label>
                <input type="password" name="current_password" class="form-input" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password Baru <span class="required">*</span></label>
                    <input type="password" name="password" class="form-input" required minlength="8">
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password <span class="required">*</span></label>
                    <input type="password" name="password_confirmation" class="form-input" required>
                </div>
            </div>
            <div class="popup-actions">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-lock-open"></i> Ubah Password
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('profileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const response = await fetch('{{ route("profile.update") }}', {
            method: 'PUT',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
            }
        });
        const data = await response.json();
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Terjadi kesalahan', 'error');
    }
});

document.getElementById('passwordForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const response = await fetch('{{ route("profile.password") }}', {
            method: 'PUT',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
            }
        });
        const data = await response.json();
        if (data.success) {
            showToast(data.message, 'success');
            e.target.reset();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Terjadi kesalahan', 'error');
    }
});
</script>
@endpush
