<?php

/**
 * Sitemap Configuration
 *
 * Comprehensive route mapping for the AC Beneran application.
 * All routes are organized by access level and category.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Application Info
    |--------------------------------------------------------------------------
    */
    'app' => [
        'name' => 'AC Beneran',
        'version' => '1.0.1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Routes (No authentication required)
    |--------------------------------------------------------------------------
    */
    'public' => [
        'title' => 'Public Routes',
        'description' => 'Accessible without authentication',
        'routes' => [
            [
                'path' => '/',
                'name' => 'home',
                'description' => 'Landing page',
                'method' => 'GET',
            ],
            [
                'path' => '/login',
                'name' => 'login',
                'description' => 'Login form',
                'method' => 'GET',
                'middleware' => 'guest',
            ],
            [
                'path' => '/login',
                'name' => 'login.post',
                'description' => 'Login handler',
                'method' => 'POST',
            ],
            [
                'path' => '/ac-anggota',
                'name' => 'ac-anggota.index',
                'description' => 'AC Anggota public page',
                'method' => 'GET',
            ],
            [
                'path' => '/modules/ac-service',
                'name' => 'modules.ac-service.index',
                'description' => 'AC Service module home',
                'method' => 'GET',
            ],
            [
                'path' => '/modules/ac-service/guest-order',
                'name' => 'modules.ac-service.guest-order.index',
                'description' => 'Guest service order form',
                'method' => 'GET',
            ],
            [
                'path' => '/modules/ac-service/guest-order',
                'name' => 'modules.ac-service.guest-order.store',
                'description' => 'Submit guest order',
                'method' => 'POST',
            ],
            [
                'path' => '/modules/ac-anggota',
                'name' => 'modules.ac-anggota.index',
                'description' => 'AC Anggota module home',
                'method' => 'GET',
            ],
            [
                'path' => '/modules/ac-anggota/guest-order',
                'name' => 'modules.ac-anggota.guest-order.index',
                'description' => 'Guest service order form',
                'method' => 'GET',
            ],
            [
                'path' => '/modules/ac-anggota/guest-order',
                'name' => 'modules.ac-anggota.guest-order.store',
                'description' => 'Submit guest order',
                'method' => 'POST',
            ],
            [
                'path' => '/modules/ac-masjid-musholla',
                'name' => 'modules.ac-masjid-musholla.index',
                'description' => 'AC Masjid Musholla module',
                'method' => 'GET',
            ],
            [
                'path' => '/modules/ac-masjid-musholla/guest-order',
                'name' => 'modules.ac-masjid-musholla.guest-order.index',
                'description' => 'Guest service order form',
                'method' => 'GET',
            ],
            [
                'path' => '/modules/ac-masjid-musholla/guest-order',
                'name' => 'modules.ac-masjid-musholla.guest-order.store',
                'description' => 'Submit guest order',
                'method' => 'POST',
            ],
            [
                'path' => '/modules/inventory',
                'name' => 'modules.inventory.index',
                'description' => 'Inventory module home',
                'method' => 'GET',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authenticated Routes (All logged-in users)
    |--------------------------------------------------------------------------
    */
    'authenticated' => [
        'title' => 'Authenticated Routes',
        'description' => 'Available to all logged-in users',
        'routes' => [
            // Dashboard
            [
                'path' => '/dashboard',
                'name' => 'dashboard',
                'description' => 'Main dashboard',
                'method' => 'GET',
            ],
            [
                'path' => '/dashboard/snapshot',
                'name' => 'dashboard.snapshot',
                'description' => 'Dashboard snapshot JSON',
                'method' => 'GET',
            ],

            // Monitoring
            [
                'path' => '/monitoring',
                'name' => 'monitoring',
                'description' => 'Service monitoring page',
                'method' => 'GET',
            ],
            [
                'path' => '/monitoring/snapshot',
                'name' => 'monitoring.snapshot',
                'description' => 'Monitoring snapshot JSON',
                'method' => 'GET',
            ],
            [
                'path' => '/monitoring/status-counts',
                'name' => 'monitoring.status-counts',
                'description' => 'Status counts API',
                'method' => 'GET',
            ],

            // Realtime
            [
                'path' => '/sync/stream',
                'name' => 'sync.stream',
                'description' => 'Real-time sync stream',
                'method' => 'GET',
            ],

            // Profile
            [
                'path' => '/profile',
                'name' => 'profile.index',
                'description' => 'User profile page',
                'method' => 'GET',
            ],
            [
                'path' => '/profile',
                'name' => 'profile.update',
                'description' => 'Update profile',
                'method' => 'PUT',
            ],
            [
                'path' => '/profile/password',
                'name' => 'profile.password',
                'description' => 'Change password',
                'method' => 'PUT',
            ],

            // Masjid
            [
                'path' => '/masjid/{masjid}',
                'name' => 'masjid.detail',
                'description' => 'Masjid detail page',
                'method' => 'GET',
            ],
            [
                'path' => '/masjid/{masjid}/history',
                'name' => 'service-order.history',
                'description' => 'Service history data',
                'method' => 'GET',
            ],
            [
                'path' => '/masjid/{masjid}/history-page',
                'name' => 'masjid.history.show',
                'description' => 'History page',
                'method' => 'GET',
            ],

            // Service Orders
            [
                'path' => '/service-order/{serviceOrder}',
                'name' => 'service-order.show',
                'description' => 'Service order detail',
                'method' => 'GET',
            ],
            [
                'path' => '/service-order/{serviceOrder}/spk',
                'name' => 'spk.print',
                'description' => 'Print SPK',
                'method' => 'GET',
            ],
            [
                'path' => '/service-order/{serviceOrder}/invoice',
                'name' => 'invoice.print',
                'description' => 'Print invoice',
                'method' => 'GET',
            ],

            // Workflow
            [
                'path' => '/workflow/{serviceOrder}/timeline',
                'name' => 'workflow.timeline',
                'description' => 'Workflow timeline',
                'method' => 'GET',
            ],
            [
                'path' => '/workflow/technicians',
                'name' => 'workflow.technicians',
                'description' => 'Technicians list',
                'method' => 'GET',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontdesk/Admin Routes
    |--------------------------------------------------------------------------
    */
    'frontdesk_admin' => [
        'title' => 'Frontdesk & Admin',
        'description' => 'Requires frontdesk or admin role',
        'roles' => ['frontdesk', 'admin'],
        'routes' => [
            // Masjid CRUD
            [
                'path' => '/masjid',
                'name' => 'masjid.store',
                'description' => 'Create Masjid',
                'method' => 'POST',
            ],
            [
                'path' => '/masjid/{masjid}',
                'name' => 'masjid.update',
                'description' => 'Update Masjid',
                'method' => 'PUT',
            ],
            [
                'path' => '/masjid/{masjid}',
                'name' => 'masjid.destroy',
                'description' => 'Delete Masjid',
                'method' => 'DELETE',
            ],

            // AC Units
            [
                'path' => '/masjid/{masjid}/ac',
                'name' => 'ac.store',
                'description' => 'Add AC unit',
                'method' => 'POST',
            ],
            [
                'path' => '/ac/bulk',
                'name' => 'ac.bulk',
                'description' => 'Bulk add AC units',
                'method' => 'POST',
            ],
            [
                'path' => '/ac/{acUnit}',
                'name' => 'ac.update',
                'description' => 'Update AC unit',
                'method' => 'PUT',
            ],
            [
                'path' => '/ac/{acUnit}',
                'name' => 'ac.destroy',
                'description' => 'Delete AC unit',
                'method' => 'DELETE',
            ],

            // Service Orders
            [
                'path' => '/service-order',
                'name' => 'service-order.store',
                'description' => 'Create service order',
                'method' => 'POST',
            ],
            [
                'path' => '/service-order/{serviceOrder}',
                'name' => 'service-order.destroy',
                'description' => 'Delete service order',
                'method' => 'DELETE',
            ],
            [
                'path' => '/service-order/{serviceOrder}/invoice',
                'name' => 'service-order.invoice-generate',
                'description' => 'Generate invoice',
                'method' => 'POST',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Manager/Admin Routes
    |--------------------------------------------------------------------------
    */
    'manager_admin' => [
        'title' => 'Manager & Admin',
        'description' => 'Requires manager or admin role',
        'roles' => ['manager', 'admin'],
        'routes' => [
            // Approvals
            [
                'path' => '/service-order/{serviceOrder}/approve',
                'name' => 'service-order.approve',
                'description' => 'Approve order',
                'method' => 'POST',
            ],
            [
                'path' => '/service-order/{serviceOrder}/cancel-approve',
                'name' => 'service-order.cancel-approve',
                'description' => 'Cancel approval',
                'method' => 'POST',
            ],
            [
                'path' => '/service-order/{serviceOrder}/manager',
                'name' => 'service-order.destroy-manager',
                'description' => 'Manager delete order',
                'method' => 'DELETE',
            ],
            [
                'path' => '/workflow/{serviceOrder}/approve-spk-invoice',
                'name' => 'workflow.approve-spk-invoice.base',
                'description' => 'Approve SPK & Invoice',
                'method' => 'POST',
            ],

            // Reports
            [
                'path' => '/reports',
                'name' => 'reports.index',
                'description' => 'Reports page',
                'method' => 'GET',
            ],
            [
                'path' => '/reports/export',
                'name' => 'reports.export',
                'description' => 'Export reports JSON',
                'method' => 'GET',
            ],

            // Workflow
            [
                'path' => '/workflow/{serviceOrder}/assign',
                'name' => 'workflow.assign',
                'description' => 'Assign technician',
                'method' => 'POST',
            ],
            [
                'path' => '/workflow/{serviceOrder}/close',
                'name' => 'workflow.close',
                'description' => 'Close workflow',
                'method' => 'POST',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin-Only Routes
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'title' => 'Admin Only',
        'description' => 'Requires admin role',
        'roles' => ['admin'],
        'routes' => [
            // User Management
            [
                'path' => '/users',
                'name' => 'users.index',
                'description' => 'User management',
                'method' => 'GET',
            ],
            [
                'path' => '/users',
                'name' => 'users.store',
                'description' => 'Create user',
                'method' => 'POST',
            ],
            [
                'path' => '/users/{user}',
                'name' => 'users.update',
                'description' => 'Update user',
                'method' => 'PUT',
            ],
            [
                'path' => '/users/{user}/reset-password',
                'name' => 'users.reset-password',
                'description' => 'Reset password',
                'method' => 'PUT',
            ],
            [
                'path' => '/users/{user}',
                'name' => 'users.destroy',
                'description' => 'Delete user',
                'method' => 'DELETE',
            ],

            // Admin Logs
            [
                'path' => '/admin/logs',
                'name' => 'admin.logs.index',
                'description' => 'Admin logs',
                'method' => 'GET',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Technician Routes
    |--------------------------------------------------------------------------
    */
    'technician' => [
        'title' => 'Technician',
        'description' => 'Technician role routes',
        'roles' => ['technician'],
        'routes' => [
            [
                'path' => '/technician',
                'name' => 'technician.dashboard',
                'description' => 'Technician dashboard',
                'method' => 'GET',
            ],
            [
                'path' => '/technician/snapshot',
                'name' => 'technician.snapshot',
                'description' => 'Technician snapshot',
                'method' => 'GET',
            ],
            [
                'path' => '/technician/spk/{serviceOrder}',
                'name' => 'technician.spk',
                'description' => 'View SPK',
                'method' => 'GET',
            ],
            [
                'path' => '/workflow/{serviceOrder}/progress',
                'name' => 'workflow.progress',
                'description' => 'Update progress',
                'method' => 'POST',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Viewer/Auditor Routes
    |--------------------------------------------------------------------------
    */
    'viewer' => [
        'title' => 'Viewer/Auditor',
        'description' => 'Viewer role routes',
        'roles' => ['viewer'],
        'routes' => [
            [
                'path' => '/viewer',
                'name' => 'viewer.dashboard',
                'description' => 'Viewer dashboard',
                'method' => 'GET',
            ],
            [
                'path' => '/viewer/snapshot',
                'name' => 'viewer.snapshot',
                'description' => 'Viewer snapshot',
                'method' => 'GET',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Routes
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'title' => 'Modules',
        'description' => 'Modular application pages',
        'routes' => [
            // AC Service Module
            [
                'path' => '/modules/ac-service',
                'name' => 'modules.ac-service.index',
                'description' => 'AC Service module',
                'module' => 'ac-service',
                'method' => 'GET',
            ],
            [
                'path' => '/modules/ac-service/dashboard',
                'name' => 'modules.ac-service.dashboard',
                'description' => 'AC Service dashboard',
                'module' => 'ac-service',
                'method' => 'GET',
                'auth' => true,
            ],
            [
                'path' => '/modules/ac-service/monitoring',
                'name' => 'modules.ac-service.monitoring',
                'description' => 'AC Service monitoring',
                'module' => 'ac-service',
                'method' => 'GET',
                'auth' => true,
            ],

            // AC Anggota Module
            [
                'path' => '/modules/ac-anggota',
                'name' => 'modules.ac-anggota.index',
                'description' => 'AC Anggota module',
                'module' => 'ac-anggota',
                'method' => 'GET',
            ],
            [
                'path' => '/modules/ac-anggota/dashboard',
                'name' => 'modules.ac-anggota.dashboard',
                'description' => 'AC Anggota dashboard',
                'module' => 'ac-anggota',
                'method' => 'GET',
                'auth' => true,
            ],
            [
                'path' => '/modules/ac-anggota/monitoring',
                'name' => 'modules.ac-anggota.monitoring',
                'description' => 'AC Anggota monitoring',
                'module' => 'ac-anggota',
                'method' => 'GET',
                'auth' => true,
            ],

            // AC Masjid Musholla Module
            [
                'path' => '/modules/ac-masjid-musholla',
                'name' => 'modules.ac-masjid-musholla.index',
                'description' => 'AC Masjid Musholla module',
                'module' => 'ac-masjid-musholla',
                'method' => 'GET',
            ],
            [
                'path' => '/modules/ac-masjid-musholla/dashboard',
                'name' => 'modules.ac-masjid-musholla.dashboard',
                'description' => 'AC Masjid Musholla dashboard',
                'module' => 'ac-masjid-musholla',
                'method' => 'GET',
                'auth' => true,
            ],
            [
                'path' => '/modules/ac-masjid-musholla/monitoring',
                'name' => 'modules.ac-masjid-musholla.monitoring',
                'description' => 'AC Masjid Musholla monitoring',
                'module' => 'ac-masjid-musholla',
                'method' => 'GET',
                'auth' => true,
            ],

            // Inventory Module
            [
                'path' => '/modules/inventory',
                'name' => 'modules.inventory.index',
                'description' => 'Inventory module',
                'module' => 'inventory',
                'method' => 'GET',
            ],
            [
                'path' => '/modules/inventory/dashboard',
                'name' => 'modules.inventory.dashboard',
                'description' => 'Inventory dashboard',
                'module' => 'inventory',
                'method' => 'GET',
                'auth' => true,
            ],
        ],
    ],
];
