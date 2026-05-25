<?php
// NOTE: This file REPLACES app/Models/User.php
// Only change: added isTechnician() and isViewer() methods + updated fillable
// All existing methods preserved exactly.

namespace App\Models;

use App\Support\UserRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $connection = 'main';

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['password' => 'hashed'];

    public function isFrontdesk(): bool
    {
        return $this->role === UserRoles::FRONTDESK;
    }

    public function isManager(): bool
    {
        return $this->role === UserRoles::MANAGER;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRoles::ADMIN;
    }

    // NEW
    public function isTechnician(): bool
    {
        return $this->role === UserRoles::TECHNICIAN;
    }

    // NEW
    public function isViewer(): bool
    {
        return $this->role === UserRoles::VIEWER;
    }

    // NEW — convenience helper for role label display
    public function roleLabel(): string
    {
        return match($this->role) {
            UserRoles::FRONTDESK => 'Front Desk',
            UserRoles::MANAGER => 'Manager',
            UserRoles::ADMIN => 'Admin',
            UserRoles::TECHNICIAN => 'Teknisi',
            UserRoles::VIEWER => 'Viewer / Auditor',
            default      => ucfirst($this->role),
        };
    }

    // NEW — check if user can access admin/manager areas
    public function hasElevatedAccess(): bool
    {
        return in_array($this->role, [UserRoles::ADMIN, UserRoles::MANAGER], true);
    }
}
