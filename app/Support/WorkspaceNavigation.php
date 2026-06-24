<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class WorkspaceNavigation
{
    public const SESSION_KEY = 'workspace_context';

    public static function build(Request $request): array
    {
        $routeName = (string) ($request->route()?->getName() ?? '');
        $workspaceKey = self::syncWorkspace($request, $routeName);
        $isHome = $routeName === 'home';
        $user = $request->user();

        $roleLinks = self::roleLinks($user, $routeName);
        $profileLink = self::link('Profil Saya', 'fas fa-user-circle', route('profile.index'), $routeName, ['profile.*']);

        if ($isHome) {
            return [
                'workspaceContext' => self::workspaceConfig($workspaceKey),
                'sidebarLinks' => [$profileLink],
                'workspaceHubLinks' => [],
                'showWorkspaceHub' => false,
            ];
        }

        $workspaceLinks = $workspaceKey ? self::workspaceLinks($workspaceKey, $routeName) : [];

        return [
            'workspaceContext' => self::workspaceConfig($workspaceKey),
            'sidebarLinks' => array_values(array_filter([
                ...$workspaceLinks,
                $profileLink,
                ...$roleLinks,
            ])),
            'workspaceHubLinks' => $workspaceLinks
                ? array_values(array_filter([
                    ...$workspaceLinks,
                    $profileLink,
                    ...$roleLinks,
                ]))
                : [],
            'showWorkspaceHub' => ! empty($workspaceLinks),
        ];
    }

    protected static function syncWorkspace(Request $request, string $routeName): ?string
    {
        $workspaceFromRoute = self::workspaceFromRoute($routeName);

        if ($workspaceFromRoute !== null && $request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $workspaceFromRoute);
        }

        return $workspaceFromRoute
            ?: ($request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null);
    }

    protected static function workspaceFromRoute(string $routeName): ?string
    {
        return match (true) {
            Str::is('ac-anggota.*', $routeName),
            Str::is('modules.ac-anggota.*', $routeName) => 'ac-anggota',

            Str::is('modules.ac-masjid-musholla.*', $routeName),
            in_array($routeName, ['dashboard', 'dashboard.snapshot', 'monitoring', 'monitoring.snapshot', 'monitoring.status-counts'], true) => 'ac-masjid-musholla',

            default => null,
        };
    }

    protected static function workspaceConfig(?string $workspaceKey): ?array
    {
        return match ($workspaceKey) {
            'ac-masjid-musholla' => [
                'key' => 'ac-masjid-musholla',
                'label' => 'AC Masjid & Musholla',
                'short_label' => 'AC Masjid',
                'hub_label' => 'Hub AC Masjid',
                'icon' => 'fas fa-mosque',
                'home_route' => 'modules.ac-masjid-musholla.index',
                'dashboard_route' => 'dashboard',
                'monitoring_route' => 'monitoring',
                'show_monitoring_badges' => true,
            ],
            'ac-anggota' => [
                'key' => 'ac-anggota',
                'label' => 'AC Anggota',
                'short_label' => 'AC Anggota',
                'hub_label' => 'Hub AC Anggota',
                'icon' => 'fas fa-users',
                'home_route' => 'ac-anggota.index',
                'dashboard_route' => 'ac-anggota.dashboard',
                'monitoring_route' => 'ac-anggota.monitoring',
                'show_monitoring_badges' => false,
            ],
            default => null,
        };
    }

    protected static function workspaceLinks(string $workspaceKey, string $routeName): array
    {
        $workspace = self::workspaceConfig($workspaceKey);

        if ($workspace === null) {
            return [];
        }

        $links = [
            self::link('Beranda', 'fas fa-home', route('home'), $routeName, ['home']),
            self::link('Katalog', 'fas fa-layer-group', route('home') . '#katalog', $routeName, []),
            self::routeLink(
                $workspace['hub_label'],
                $workspace['icon'],
                $workspace['home_route'],
                $routeName,
                match ($workspaceKey) {
                    'ac-masjid-musholla' => ['modules.ac-masjid-musholla.index'],
                    'ac-anggota' => ['ac-anggota.index', 'modules.ac-anggota.index'],
                }
            ),
            self::routeLink(
                $workspace['short_label'] . ' Dashboard',
                'fas fa-th-large',
                $workspace['dashboard_route'],
                $routeName,
                match ($workspaceKey) {
                    'ac-masjid-musholla' => ['dashboard', 'modules.ac-masjid-musholla.dashboard'],
                    'ac-anggota' => ['ac-anggota.dashboard', 'modules.ac-anggota.dashboard'],
                }
            ),
            self::routeLink(
                $workspace['short_label'] . ' Monitoring',
                'fas fa-chart-line',
                $workspace['monitoring_route'],
                $routeName,
                match ($workspaceKey) {
                    'ac-masjid-musholla' => ['monitoring', 'modules.ac-masjid-musholla.monitoring'],
                    'ac-anggota' => ['ac-anggota.monitoring', 'modules.ac-anggota.monitoring'],
                },
                [
                    'class' => 'monitoring-link',
                    'show_badges' => $workspace['show_monitoring_badges'],
                ]
            ),
        ];

        return array_values(array_filter($links));
    }

    protected static function roleLinks(?User $user, string $routeName): array
    {
        if (! $user) {
            return [];
        }

        $links = [];

        if ($user->isAdmin()) {
            $links[] = self::routeLink('Manajemen User', 'fas fa-users-cog', 'users.index', $routeName, ['users.*']);
            $links[] = self::routeLink('Dashboard Log', 'fas fa-clipboard-list', 'admin.logs.index', $routeName, ['admin.logs.*']);
        }

        if ($user->isManager() || $user->isAdmin()) {
            $links[] = self::routeLink('Laporan', 'fas fa-chart-bar', 'reports.index', $routeName, ['reports.*']);
        }

        if ($user->isTechnician()) {
            $links[] = self::routeLink('Dashboard Teknisi', 'fas fa-tools', 'technician.dashboard', $routeName, ['technician.*']);
        }

        if ($user->isViewer()) {
            $links[] = self::routeLink('Dashboard Viewer', 'fas fa-eye', 'viewer.dashboard', $routeName, ['viewer.*']);
        }

        return array_values(array_filter($links));
    }

    protected static function routeLink(
        string $label,
        string $icon,
        string $routeName,
        string $currentRouteName,
        array $activePatterns,
        array $extra = []
    ): ?array {
        if (! Route::has($routeName)) {
            return null;
        }

        return self::link($label, $icon, route($routeName), $currentRouteName, $activePatterns, $extra);
    }

    protected static function link(
        string $label,
        string $icon,
        string $url,
        string $currentRouteName,
        array $activePatterns = [],
        array $extra = []
    ): array {
        $active = collect($activePatterns)->contains(fn (string $pattern): bool => Str::is($pattern, $currentRouteName));

        return array_merge([
            'label' => $label,
            'icon' => $icon,
            'url' => $url,
            'active' => $active,
            'class' => '',
            'show_badges' => false,
        ], $extra);
    }
}
