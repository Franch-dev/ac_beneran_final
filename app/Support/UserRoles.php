<?php

namespace App\Support;

final class UserRoles
{
    public const FRONTDESK = 'frontdesk';
    public const MANAGER = 'manager';
    public const ADMIN = 'admin';
    public const TECHNICIAN = 'technician';
    public const VIEWER = 'viewer';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [
            self::FRONTDESK,
            self::MANAGER,
            self::ADMIN,
            self::TECHNICIAN,
            self::VIEWER,
        ];
    }
}

