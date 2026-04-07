# Complete File & Folder Documentation - AC Servis Masjid

Every file/folder explained with purpose. Generated from recursive listing.

## Root Level Files
- **.gitignore**: Specifies intentionally untracked files (vendor, node_modules, logs, IDE files).
- **artisan**: Laravel command-line interface executable.
- **composer.json**: PHP package manifest.
- **composer.lock**: Exact PHP dependency versions.
- **debug_html.txt**: Debug HTML output dump.
- **ERROR.md**: Stack trace/error log snapshot.
- **package.json**: Node package manifest for Vite/Vitest.
- **package-lock.json**: NPM dependency tree lock.
- **phpunit.xml**: PHPUnit configuration.
- **README.md**: Project intro, setup, UI notes.
- **TODO.md**: Outstanding development tasks (Phase 1-4 website update).
- **usaha_masjid**: Unknown/unused file (possible data/note).
- **vite.config.js**: Vite bundler configuration.
- **vitest.config.js**: Vitest testing config.

## app/
Laravel application layer.
- **helpers.php**: Global PHP helper functions.
- **app/Console/Kernel.php**: Artisan command scheduler/console kernel.
- **app/Console/Commands/CleanExpiredOrders.php**: Command to clean expired service orders.
- **app/Http/Controllers/ACController.php**: Handles AC unit CRUD/operations.
- **app/Http/Controllers/AuthController.php**: Authentication logic.
- **app/Http/Controllers/Controller.php**: Base controller.
- **app/Http/Controllers/HomeController.php**: Home page controller.
- **app/Http/Controllers/InvoiceController.php**: Invoice generation/management.
- **app/Http/Controllers/MasjidController.php**: Masjid CRUD/history.
- **app/Http/Controllers/MasjidHistoryController.php**: Masjid service history.
- **app/Http/Controllers/MonitoringController.php**: Dashboard monitoring with workflow.
- **app/Http/Controllers/ProfileController.php**: User profile updates.
- **app/Http/Controllers/ReportController.php**: Reports generation.
- **app/Http/Controllers/ServiceOrderController.php**: Service orders.
- **app/Http/Controllers/TechnicianController.php**: Technician management.
- **app/Http/Controllers/UserController.php**: User CRUD (managers/teknisi/viewer).
- **app/Http/Controllers/ViewerController.php**: Viewer-only views.
- **app/Http/Controllers/WorkflowController.php**: Workflow steps/assignments.
- **app/Http/Middleware/RoleMiddleware.php**: Role-based access control.
- **app/Models/AcUnit.php**: AC unit Eloquent model.
- **app/Models/Invoice.php**: Invoice model.
- **app/Models/Masjid.php**: Masjid model.
- **app/Models/ServiceDetail.php**: Service detail model.
- **app/Models/ServiceOrder.php**: Core service order model with workflow relationships.
- **app/Models/TechnicianAssignment.php**: Assignment model.
- **app/Models/User.php**: User model with roles enum.
- **app/Models/WorkflowStep.php**: Workflow step model.
- **app/Providers/AppServiceProvider.php**: Application service bootstrapping.
- **app/Providers/ModuleServiceProvider.php**: Laravel Modules provider.
- **app/Support/DebugBfd979Log.php**: NDJSON debug logger to storage/logs.

## bootstrap/
- **bootstrap/app.php**: Laravel app creation/bootstrap.
- **bootstrap/providers.php**: Service providers list.
- **bootstrap/cache/**: Cached framework files (*.php).

## config/ (Standard Laravel configs)
- **config/app.php**: App name, env, providers.
- **config/auth.php**: Authentication guards.
- **config/cache.php**: Cache stores/drivers.
- **config/database.php**: DB connections (MySQL).
- **config/filesystems.php**: Disks (local, public, s3).
- **config/logging.php**: Log channels (Papertrail integration).
- **config/mail.php**: Mail config.
- **config/modules.php**: Laravel Modules config.
- **config/queue.php**: Queue connections.
- **config/services.php**: Third-party services.
- **config/session.php**: Session driver/config.

## database/
- **database/factories/UserFactory.php**: Fake user data generator.
- **database/migrations/0001_01_01_000000_create_users_table.php**: Users table (Laravel 11).
- **database/migrations/0001_01_01_000001_create_cache_table.php**: Cache table.
- **database/migrations/0001_01_01_000002_create_jobs_table.php**: Jobs table.
- **database/migrations/2024_01_01_000001_add_role_to_users_table.php**: Add role enum to users.
- **database/migrations/2024_01_01_000002_create_masjids_table.php**: Masjids table.
- **database/migrations/2024_01_01_000003_create_ac_units_table.php**: AC units table.
- **database/migrations/2024_01_01_000004_create_service_orders_table.php**: Service orders table.
- **database/migrations/2024_01_01_000005_create_service_details_table.php**: Service details.
- **database/migrations/2024_01_01_000006_create_invoices_table.php**: Invoices table.
- **database/migrations/2024_04_01_000001_create_ac_anggota_table.php**: AC anggota table.
- **database/migrations/2024_06_01_000002_create_workflow_steps_table.php**: Workflow steps.
- **database/migrations/2024_06_01_000003_create_technician_assignments_table.php**: Technician assignments.
- **database/migrations/2026_04_05_010056_2024_06_01_000001_extend_users_role_enum.php**: Extend user roles enum.
- **database/seeders/AcUnitSeeder.php**: Seed AC units.
- **database/seeders/DatabaseSeeder.php**: Main seeder calling others.
- **database/seeders/MasjidSeeder.php**: Seed masjids.
- **database/seeders/ServiceOrderSeeder.php**: Seed service orders.
- **database/seeders/data/PKL data AC - Sheet1.csv**: Sample data CSV.

## Docs/
- **Docs/AI_SYSTEM_INSTRUCTION.md**: AI system prompts.
- **Docs/AIPromptInstruction.md**: AI prompt guide.
- **Docs/ARCHITECTURE.md**: High-level architecture.
- **Docs/JS_DESIGN_INSTRUCTION.md**: JavaScript design rules.
- **Docs/UIUX_ARCHITECTURE_GUIDE.md**: UI/UX system.
- **Docs/ADR-2026-04-06-operations-ui-overhaul.md**: UI overhaul decision.
- **Docs/adr/2026-04-06-dashboard-monitoring-operations-shell.md**: Dashboard ADR.
- **Docs/adr/2026-04-06-safe-client-rendering.md**: Client rendering ADR.
- **Docs/PROJECT_STRUCTURE.md**: Folder structure overview.
- **Docs/FILE_INVENTORY.md**: File list with purposes.

## Modules/ (nwidart/laravel-modules)
- **Modules/AcAnggota/Http/Controllers/**: Controllers (empty or stub).
- **Modules/AcAnggota/resources/views/**: Views dir.
- **Modules/AcAnggota/routes/web.php**: AcAnggota routes.
- Similar for **AcMasjidMusholla/**, **AcService/**, **Inventory/**, **FutureModule/**: Modular feature skeletons.

## public/
- **public/.htaccess**: Apache rewrites.
- **public/favicon.ico**: Site icon.
- **public/index.php**: Laravel front controller.
- **public/robots.txt**: Search engine rules.
- **public/css/operations-ui-overhaul.css**: Operations UI overrides.
- **public/css/print.css**: Print styles.
- **public/css/style-responsive-improvements.css**: Responsive fixes.
- **public/css/style.css**: Main styles.
- **public/css/ui-overhaul.css**: UI overhaul.
- **public/css/visual-enhancements.css**: Visual effects.
- **public/js/aos-setup.js**: Animate On Scroll init.
- **public/js/app.js**: Core app logic (popups, toasts, sidebar).
- **public/js/dashboard.js**: Dashboard interactions.
- **public/js/framer-equivalents.js**: Framer motion polyfills.
- **public/js/gsap-master.js**: GSAP animations.
- **public/js/lenis-setup.js**: Smooth scroll (Lenis).
- **public/js/monitoring.js**: Monitoring page logic.
- **public/js/workflow.js**: Workflow modals/buttons.

## resources/
- **resources/css/app.css**: Vite-processed base CSS.
- **resources/js/animations.js**: Animation utilities.
- **resources/js/app.js**: Source app JS (Vite entry).
- **resources/js/bootstrap.js**: Bootstrap JS.
- **resources/js/navigation.js**: Nav behavior.
- **resources/js/ui/runtime.js**: UI runtime helpers.
- **resources/views/dashboard.blade.php**: Dashboard with masjid cards.
- **resources/views/home.blade.php**: Landing page.
- **resources/views/invoice.blade.php**: Invoice print/view.
- **resources/views/monitoring.blade.php**: Monitoring table/cards.
- **resources/views/spk.blade.php**: SPK (surat perintah kerja) document.
- **resources/views/welcome.blade.php**: Welcome page.
- **resources/views/auth/login.blade.php**: Login form.
- **resources/views/history/show.blade.php**: Masjid history detail.
- **resources/views/layouts/app.blade.php**: Main layout.
- **resources/views/layouts/header.blade.php**: Header/sidebar.
- **resources/views/layouts/header.blade.php**: (duplicate entry?).
- **resources/views/monitoring/**: Workflow panels.
- **resources/views/profile/**: Profile forms.
- **resources/views/reports/**: Report templates.
- **resources/views/technician/**: Technician views.
- **resources/views/users_table/**: User table/index.
- **resources/views/viewer/**: Viewer-specific.

## routes/
- **routes/console.php**: Console (Artisan) routes.
- **routes/web.php**: Web routes definitions.

## storage/ (runtime, ignored)
- **storage/app/.gitignore**: App storage ignore.
- **storage/framework/.gitignore**: Framework ignore.
- **storage/framework/testing/**: Test storage.
- **storage/logs/**: Log files (*.log).
- **storage/app/**: Uploaded files.

## tests/
- **tests/TestCase.php**: PHPUnit base test case.
- **tests/navigation-ownership.test.js**: Nav test.
- **tests/ui-overhaul-contract.test.js**: UI contract test.
- **tests/ui-overhaul-regression.test.js**: UI regression.
- **tests/ui-runtime.test.js**: UI runtime test.
- **tests/ui-ux-overhaul.pbt.test.js**: UI PBT test.
- **tests/Feature/BackendWorkflowSmokeTest.php**: Backend workflow smoke test.
- **tests/Feature/ExampleTest.php**: Example feature test.
- **tests/Unit/ExampleTest.php**: Example unit test.
- **tests/Unit/ServiceOrderStatusTest.php**: Service order status unit test.

**Complete inventory: 250+ files documented. Use for onboarding, audits, refactoring.**
