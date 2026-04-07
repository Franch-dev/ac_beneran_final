@extends('layouts.app')

@section('title', 'Manajemen User - AC Servis Masjid')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-users-cog"></i> Manajemen User</h1>
            <p class="page-subtitle">Kelola semua pengguna platform</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-primary" onclick="openPopup('addUserPopup')">
                <i class="fas fa-user-plus"></i> Tambah User
            </button>
        </div>
    </div>

    <!-- Role Summary Cards -->
    <div class="summary-grid" style="margin-bottom: 24px;">
        @foreach(['frontdesk' => ['Front Desk','fas fa-headset','bg-primary'], 'manager' => ['Manager','fas fa-user-tie','bg-success'], 'admin' => ['Admin','fas fa-user-shield','bg-danger'], 'technician' => ['Teknisi','fas fa-tools','bg-warning'], 'viewer' => ['Viewer','fas fa-eye','bg-info']] as $role => $meta)
        <div class="summary-card">
            <div class="summary-icon {{ $meta[2] }}">
                <i class="{{ $meta[1] }}"></i>
            </div>
            <div class="summary-content">
                <div class="summary-num">{{ $roleCounts[$role] ?? 0 }}</div>
                <div class="summary-label">{{ $meta[0] }}</div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Search & Filter -->
    <div class="search-bar">
        <form action="{{ route('users.index') }}" method="GET" class="search-form">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="search" class="search-input" placeholder="Cari nama atau email..."
                       value="{{ request('search') }}">
            </div>
            <select name="role" class="form-select" style="width:auto">
                <option value="">Semua Role</option>
                @foreach(['frontdesk' => 'Front Desk', 'manager' => 'Manager', 'admin' => 'Admin', 'technician' => 'Teknisi', 'viewer' => 'Viewer'] as $val => $label)
                    <option value="{{ $val }}" {{ request('role') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->anyFilled(['search', 'role']))
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    <!-- Users Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:36px;height:36px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.875rem;flex-shrink:0">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-bold">{{ $user->name }}</div>
                                @if($user->id === auth()->id())
                                    <span style="font-size:0.7rem;color:var(--primary);font-weight:600">(Anda)</span>
                                @endif>
                            </div>
                        </div>
                    </td>
                    <td class="text-muted">{{ $user->email }}</td>
                    <td>
                        @php
                            $roleColors = ['frontdesk' => 'primary', 'manager' => 'success', 'admin' => 'danger', 'technician' => 'warning', 'viewer' => 'info'];
                            $roleLabels = ['frontdesk' => 'Front Desk', 'manager' => 'Manager', 'admin' => 'Admin', 'technician' => 'Teknisi', 'viewer' => 'Viewer'];
                        @endphp
                        <span class="role-badge role-{{ $user->role }}">{{ $roleLabels[$user->role] ?? $user->role }}</span>
                    </td>
                    <td class="text-muted text-sm">{{ $user->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-sm btn-secondary"
                                onclick="openEditUser({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}', '{{ $user->role }}')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-warning"
                                onclick="openResetPassword({{ $user->id }}, '{{ addslashes($user->name) }}')">
                                <i class="fas fa-key"></i> Reset
                            </button>
                            @if($user->id !== auth()->id())
                            <button class="btn btn-sm btn-danger"
                                onclick="confirmDeleteUser({{ $user->id }}, '{{ addslashes($user->name) }}')">
                                <i class="fas fa-trash"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-users"></i></div>
                            <h3>Tidak Ada User</h3>
                            <p>Belum ada user yang terdaftar.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top:16px">{{ $users->links() }}</div>
</div>

<!-- ===== POPUPS ===== -->

<!-- Add User Popup -->
<div class="popup popup-lg" id="addUserPopup">
    <div class="popup-header">
        <h3><i class="fas fa-user-plus"></i> Tambah User Baru</h3>
        <button class="popup-close" onclick="closePopup('addUserPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                <input type="text" id="addName" class="form-input" placeholder="Nama lengkap...">
            </div>
            <div class="form-group">
                <label class="form-label">Role <span class="required">*</span></label>
                <select id="addRole" class="form-select">
                    <option value="frontdesk">Front Desk</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                    <option value="technician">Teknisi</option>
                    <option value="viewer">Viewer / Auditor</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Email <span class="required">*</span></label>
            <input type="email" id="addEmail" class="form-input" placeholder="email@example.com">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Password <span class="required">*</span></label>
                <input type="password" id="addPassword" class="form-input" placeholder="Min. 8 karakter">
            </div>
            <div class="form-group">
                <label class="form-label">Konfirmasi Password <span class="required">*</span></label>
                <input type="password" id="addPasswordConfirm" class="form-input" placeholder="Ulangi password">
            </div>
        </div>
        <div class="popup-actions">
            <button class="btn btn-primary" onclick="submitAddUser()">
                <i class="fas fa-save"></i> Simpan
            </button>
            <button class="btn btn-secondary" onclick="closePopup('addUserPopup')">Batal</button>
        </div>
    </div>
</div>

<!-- Edit User Popup -->
<div class="popup popup-lg" id="editUserPopup">
    <div class="popup-header">
        <h3><i class="fas fa-user-edit"></i> Edit User</h3>
        <button class="popup-close" onclick="closePopup('editUserPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <input type="hidden" id="editUserId">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" id="editName" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select id="editRole" class="form-select">
                    <option value="frontdesk">Front Desk</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                    <option value="technician">Teknisi</option>
                    <option value="viewer">Viewer / Auditor</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" id="editEmail" class="form-input">
        </div>
        <div class="popup-actions">
            <button class="btn btn-primary" onclick="submitEditUser()">
                <i class="fas fa-save"></i> Simpan
            </button>
            <button class="btn btn-secondary" onclick="closePopup('editUserPopup')">Batal</button>
        </div>
    </div>
</div>

<!-- Reset Password Popup -->
<div class="popup" id="resetPasswordPopup">
    <div class="popup-header">
        <h3><i class="fas fa-key"></i> Reset Password</h3>
        <button class="popup-close" onclick="closePopup('resetPasswordPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <input type="hidden" id="resetUserId">
        <p class="text-muted" style="margin-bottom:16px">Reset password untuk: <strong id="resetUserName"></strong></p>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Password Baru</label>
                <input type="password" id="newPassword" class="form-input" placeholder="Min. 8 karakter">
            </div>
            <div class="form-group">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" id="newPasswordConfirm" class="form-input">
            </div>
        </div>
        <div class="popup-actions">
            <button class="btn btn-warning" onclick="submitResetPassword()">
                <i class="fas fa-key"></i> Reset
            </button>
            <button class="btn btn-secondary" onclick="closePopup('resetPasswordPopup')">Batal</button>
        </div>
    </div>
</div>

<!-- Delete Confirm Popup -->
<div class="popup" id="deleteUserPopup">
    <div class="popup-header">
        <h3><i class="fas fa-exclamation-triangle text-danger"></i> Hapus User</h3>
        <button class="popup-close" onclick="closePopup('deleteUserPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <p>Anda yakin ingin menghapus <strong id="deleteUserName"></strong>?</p>
        <p class="text-danger text-sm">Tindakan ini tidak dapat dibatalkan.</p>
        <div class="popup-actions">
            <button class="btn btn-danger" id="deleteUserConfirmBtn">
                <i class="fas fa-trash"></i> Hapus
            </button>
            <button class="btn btn-secondary" onclick="closePopup('deleteUserPopup')">Batal</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const ROUTES_USERS = {
    store:  '{{ route("users.store") }}',
    update: (id) => `/users/${id}`,
    reset:  (id) => `/users/${id}/reset-password`,
    destroy:(id) => `/users/${id}`,
};

function openEditUser(id, name, email, role) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editName').value  = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editRole').value  = role;
    openPopup('editUserPopup');
}

function openResetPassword(id, name) {
    document.getElementById('resetUserId').value    = id;
    document.getElementById('resetUserName').textContent = name;
    document.getElementById('newPassword').value    = '';
    document.getElementById('newPasswordConfirm').value = '';
    openPopup('resetPasswordPopup');
}

function confirmDeleteUser(id, name) {
    document.getElementById('deleteUserName').textContent = name;
    document.getElementById('deleteUserConfirmBtn').onclick = () => submitDeleteUser(id);
    openPopup('deleteUserPopup');
}

async function submitAddUser() {
    const payload = {
        name:                  document.getElementById('addName').value,
        email:                 document.getElementById('addEmail').value,
        role:                  document.getElementById('addRole').value,
        password:              document.getElementById('addPassword').value,
        password_confirmation: document.getElementById('addPasswordConfirm').value,
    };
    try {
        await apiFetch(ROUTES_USERS.store, 'POST', payload);
        closePopup('addUserPopup');
        showToast('User berhasil ditambahkan!');
        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        showToast(err.message || 'Gagal menambahkan user', 'error');
    }
}

async function submitEditUser() {
    const id = document.getElementById('editUserId').value;
    const payload = {
        name:  document.getElementById('editName').value,
        email: document.getElementById('editEmail').value,
        role:  document.getElementById('editRole').value,
    };
    try {
        await apiFetch(ROUTES_USERS.update(id), 'PUT', payload);
        closePopup('editUserPopup');
        showToast('User berhasil diperbarui!');
        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        showToast(err.message || 'Gagal memperbarui user', 'error');
    }
}

async function submitResetPassword() {
    const id = document.getElementById('resetUserId').value;
    const payload = {
        password:              document.getElementById('newPassword').value,
        password_confirmation: document.getElementById('newPasswordConfirm').value,
    };
    try {
        await apiFetch(ROUTES_USERS.reset(id), 'PUT', payload);
        closePopup('resetPasswordPopup');
        showToast('Password berhasil direset!');
    } catch (err) {
        showToast(err.message || 'Gagal reset password', 'error');
    }
}

async function submitDeleteUser(id) {
    try {
        await apiFetch(ROUTES_USERS.destroy(id), 'DELETE');
        closePopup('deleteUserPopup');
        showToast('User berhasil dihapus');
        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        showToast(err.message || 'Gagal menghapus user', 'error');
    }
}
</script>
@endpush

