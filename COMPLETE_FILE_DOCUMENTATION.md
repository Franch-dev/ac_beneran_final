# Complete File &amp; Folder Documentation - AC Servis Masjid (Updated to Recent Changes)

Every file/folder explained with purpose. Updated from latest project scan.

## Root Level Files
- **.gitignore**: Clean standard ignores (Laravel, node_modules, vendor, storage, temps, IDE/AI dirs). config/ tracked.
- **artisan**: Laravel CLI.
- **composer.json/.lock**: PHP deps.
- **package.json/.lock**: Node/Vite deps.
- **phpunit.xml**: PHPUnit config.
- **README.md**: Project intro.
- **TODO.md**: Pending tasks.
- **vite.config.js**: Vite bundler.
- **vitest.config.js**: Vitest tests.
- **usaha_masjid**: Unused note/data (ignored).

## app/ (Core Laravel)
- **helpers.php**: Helpers.
- **Console/Kernel.php**: Scheduler.
- **Console/Commands/CleanExpiredOrders.php**: Cleanup command.
- **Http/Controllers/**: 
  | Controller | Purpose |
  |------------|---------|
  | AcAnggotaPageController.php | AC Anggota pages |
  | ACController.php | AC units |
  | AdminLogController.php | Admin logs |
  | AuthController.php | Auth |
  | BackendOpsController.php | Backend ops |
  | HomeController.php | Home |
  | InvoiceController.php | Invoices |
  | MasjidController.php | Masjids |
  | MasjidHistoryController.php | History |
  | MonitoringController.php | Dashboard monitoring |
  | ProfileController.php | Profiles |
  | ReportController.php | Reports |
  | ServiceOrderController.php | Service orders |
  | SyncController.php | Sync |
  | TechnicianController.php | Technicians |
  | UserController.php | Users |
  | ViewerController.php | Viewer views |
  | WorkflowController.php | Workflow |
- **Http/Middleware/**: RoleMiddleware.php (roles), CspReportOnlyMiddleware.php (CSP), SecurityHeadersMiddleware.php (security).
- **Models/**: AcUnit, Invoice, Masjid, ServiceDetail, ServiceOrder, SyncEvent, TechnicianAssignment, User, WorkflowStep.
- **Providers/**: AppServiceProvider.php, ModuleServiceProvider.php.
- **Services/Database/**: DatabaseHealthService.php.
- **Services/Skills/**: Skill services.
- **Support/**: DebugBfd979Log.php, RealtimeSync.php, SqlDateExpressions.php, UserRoles.php.

## bootstrap/
- **app.php**: App bootstrap.
- **providers.php**: Providers.
- **cache/**: Cached configs (ignored).

## config/ (Tracked)
- Standard Laravel + skills.php, modules.php (AC modules).

## database/
- **factories/UserFactory.php**.
- **migrations/**: All tables + new sync/skill/index migrations (2026_*).
- **seeders/**: DatabaseSeeder.php, AcUnit/Masjid/ServiceOrderSeeder.php.
- **testing/**: SQLite test DBs.

## Docs/ (Ignored)
- AI/UI/JS/architecture docs, ADRs.

## Modules/ (Laravel Modules)
- AcAnggota, AcMasjidMusholla, AcService, Inventory, FutureModule: Http/Controllers/resources/routes.

## public/ (Assets)
- **css/**: liquid-glass*.css, operations-ui-overhaul.css, ui-overhaul.css, style*.css.
- **js/**: liquid-glass.js, gsap-master.js, lenis-setup.js, aos-setup.js, app.js, monitoring.js, workflow.js, dashboard.js, framer-equivalents.js.
- .htaccess, index.php, robots.txt.

## resources/
- **css/app.css**.
- **js/**: animations.js, app.js, bootstrap.js, navigation.js, ui/runtime.js.
- **views/**: dashboard, monitoring, layouts, ac-anggota, admin, auth, technician, viewer, users_table, profile, reports.

## routes/
- api.php, console.php, web.php.

## storage/ (Ignored runtime)
- app, framework (sessions/cache/views/logs).

## tests/
- JS Vitest UI tests, PHP Feature/Unit (BackendWorkflowSmokeTest, ServiceOrderStatusTest).

**Updated: Added new controllers (AcAnggotaPage, AdminLog, BackendOps, Sync), middleware (CSP/Security), models (SyncEvent), migrations (sync/skills/indexes), liquid-glass UI, services, api.php, ac-anggota views. Temps ignored. Total ~300 files.**

