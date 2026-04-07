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

    <div class="profile-card" style="max-width:560px">
        <div class="profile-header" style="text-align:center;padding:32px 24px 24px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-radius:var(--radius-lg) var(--radius-lg) 0 0">
            <div class="profile-avatar" style="width:96px;height:96px;margin:0 auto 16px;border-radius:50%;background:#fff;color:var(--primary);font-size:2rem;font-weight:700;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 32px rgba(0,0,0,0.15)">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="profile-name" style="font-size:1.5rem;font-weight:700;margin-bottom:4px">{{ $user->name }}</div>
            <div class="profile-role" style="font-size:1rem;opacity:0.95;text-transform:uppercase;letter-spacing:0.05em;font-weight:500">
                {{ $user->roleLabel() }}
            </div>
        </div>

        <div class="profile-body" style="padding:32px 24px;border-radius:0 0 var(--radius-lg) var(--radius-lg);background:var(--bg-card)">
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
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>

            <div class="divider"></div>

            <div style="font-weight:600;margin-bottom:16px;font-size:1.0625rem">
                <i class="fas fa-key" style="color:var(--warning);margin-right:8px"></i>
                Ubah Password
            </div>

            <form id="passwordForm">
                <input type="hidden" name="user_id" value="{{ $user->id }}">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password Saat Ini</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password" class="form-input" required minlength="8">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" class="form-input" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-lock-open"></i> Ubah Password
                    </button>
                </div>
            </form>
        </div>
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

