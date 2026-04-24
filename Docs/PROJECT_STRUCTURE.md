# AC Servis Masjid - Project Structure Documentation

## Root Level
- **.gitignore**: Ignores unnecessary files like node_modules, vendor, logs, IDE files.
- **artisan**: Laravel CLI tool for migrations, seeding, caching.
- **composer.json/lock**: PHP dependencies management.
- **package.json/lock**: Node.js dependencies for Vite builds.
- **README.md**: Project overview, setup, UI notes.
- **TODO.md**: Current development tasks (website update phases).
- **vite.config.js**: Frontend bundling config.
- **Website-Update/**: Temporary folder for new controller/views during migration.

## app/
Laravel core application code.
- **Console/**: Artisan commands (Kernel.php).
- **Http/Controllers/**: Web controllers (ACController, MonitoringController, WorkflowController, UserController, etc.).
- **Http/Middleware/**: Request handling middleware.
- **Models/**: Eloquent models (AcUnit, Masjid, ServiceOrder, WorkflowStep, TechnicianAssignment, User, Invoice).
- **Providers/**: Service providers (AppServiceProvider, ModuleServiceProvider).
- **Support/**: Custom helpers/debug (DebugBfd979Log.php).

## bootstrap/
Laravel bootstrap.
- **app.php/providers.php**: App initialization.
- **cache/**: Cached config/services (ignored).

## config/
Configuration files.
- **app.php, auth.php, database.php, etc.**: App settings, DB, mail, queue, session.

## database/
Database related.
- **factories/**: Model factories (UserFactory).
- **migrations/**: Schema migrations (users, masjids, ac_units, service_orders, workflow_steps, technician_assignments).
- **seeders/**: Data seeders (DatabaseSeeder, MasjidSeeder, AcUnitSeeder), data folder.

## Docs/
Documentation.
- **ARCHITECTURE.md**: Overall system architecture.
- **UIUX_ARCHITECTURE_GUIDE.md**: UI design system.
- **JS_DESIGN_INSTRUCTION.md**: JavaScript patterns.
- **ADR-*.md**: Architectural decision records.
- **AI*.md**: AI prompt instructions.

## Modules/
Modular features (using nwidart/laravel-modules).
- **AcAnggota/**, **AcMasjidMusholla/**, **AcService/**, **Inventory/**, **FutureModule/**: Controllers, routes, views for specific modules.

## public/
Static assets (served directly).
- **css/**: Stylesheets (style.css, ui-overhaul.css, operations-ui-overhaul.css).
- **js/**: Client scripts (app.js, dashboard.js, monitoring.js, workflow.js, gsap/lenis/AOS setups).
- **index.php**: Laravel entry point.
- **build/**: Vite compiled assets (ignored).

## resources/
Source assets.
- **css/app.css**: Base styles.
- **js/**: Source scripts (app.js, animations.js, bootstrap.js, navigation.js).
- **views/**: Blade templates (dashboard, monitoring, layouts, auth, users, profile, reports, etc.).

## routes/
Route definitions.
- **web.php**: Main web routes.
- **console.php**: Artisan commands routes.

## storage/
Runtime data (ignored mostly).
- **app/**, **framework/**: Sessions, cache, views.
- **logs/**: Application logs.

## tests/
No test suites are included in this repository.

This structure follows Laravel best practices with modular extensions and Vite frontend.
