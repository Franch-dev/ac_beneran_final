<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

final class ModulePageRegistry
{
    public static function current(): array
    {
        $currentModule = session('current_module');

        return match ($currentModule) {
            'ac-anggota' => self::anggota(),
            'ac-masjid-musholla' => self::masjidMusholla(),
            default => self::default(),
        };
    }

    public static function default(): array
    {
        return [
            'label' => 'AC Service',
            'pages' => array_values(array_filter([
                self::page('Dashboard', 'fas fa-th-large', 'dashboard'),
                self::page('Monitoring', 'fas fa-chart-line', 'monitoring'),
            ])),
        ];
    }

    public static function masjidMusholla(): array
    {
        return [
            'label' => 'AC Masjid & Musholla',
            'pages' => array_values(array_filter([
                self::page('Dashboard', 'fas fa-th-large', 'modules.ac-masjid-musholla.dashboard'),
                self::page('Database', 'fas fa-database', 'modules.ac-masjid-musholla.database'),
                self::page('Service Orders', 'fas fa-clipboard-list', 'modules.ac-masjid-musholla.service-orders'),
                self::page('Data Masjid', 'fas fa-mosque', 'modules.ac-masjid-musholla.masjid-data'),
                self::page('Data AC', 'fas fa-snowflake', 'modules.ac-masjid-musholla.ac-data'),
                self::page('Monitoring', 'fas fa-chart-line', 'modules.ac-masjid-musholla.monitoring'),
            ])),
        ];
    }

    public static function anggota(): array
    {
        return [
            'label' => 'AC Anggota',
            'pages' => array_values(array_filter([
                self::page('Dashboard', 'fas fa-th-large', 'modules.ac-anggota.dashboard'),
                self::page('Database', 'fas fa-database', 'modules.ac-anggota.database'),
                self::page('Service Orders', 'fas fa-clipboard-list', 'modules.ac-anggota.service-orders'),
                self::page('Data Anggota', 'fas fa-users', 'modules.ac-anggota.anggota-data'),
                self::page('Data AC', 'fas fa-snowflake', 'modules.ac-anggota.ac-data'),
                self::page('Monitoring', 'fas fa-chart-line', 'modules.ac-anggota.monitoring'),
            ])),
        ];
    }

    private static function page(string $label, string $icon, string $routeName): ?array
    {
        if (! Route::has($routeName)) {
            return null;
        }

        return [
            'label' => $label,
            'icon' => $icon,
            'route' => $routeName,
            'url' => route($routeName),
            'active' => request()->routeIs($routeName),
        ];
    }
}
