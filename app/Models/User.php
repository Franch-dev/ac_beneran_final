<?php
// NOTE: This file REPLACES app/Models/User.php
// Only change: added isTechnician() and isViewer() methods + updated fillable
// All existing methods preserved exactly.

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $connection = 'main';

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['password' => 'hashed'];

    public function isFrontdesk(): bool
    {
        return $this->role === 'frontdesk';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // NEW
    public function isTechnician(): bool
    {
        return $this->role === 'technician';
    }

    // NEW
    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    // NEW — convenience helper for role label display
    public function roleLabel(): string
    {
        return match($this->role) {
            'frontdesk'  => 'Front Desk',
            'manager'    => 'Manager',
            'admin'      => 'Admin',
            'technician' => 'Teknisi',
            'viewer'     => 'Viewer / Auditor',
            default      => ucfirst($this->role),
        };
    }

    // NEW — check if user can access admin/manager areas
    public function hasElevatedAccess(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }
}

