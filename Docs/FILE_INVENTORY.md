# AC Servis Masjid - Complete File Inventory

Generated from current project structure. **Short purpose for each file/folder.**

## Root (15 files, 15 dirs)
```
.editorconfig: Coding style config.
.env: Environment variables (ignored).
.env.example: Env template.
.gitattributes: Git attributes.
.gitignore: Git ignore rules (cleaned).
artisan: Laravel CLI.
composer.json: PHP deps.
composer.lock: PHP deps lock.
debug_html.txt: Debug output.
ERROR.md: Error log snapshot.
package.json: NPM deps.
package-lock.json: NPM lock.
README.md: Project overview/setup.
TODO.md: Dev tasks.
vite.config.js: Vite bundler.
- app/: Laravel app code.
- bootstrap/: Bootstrap.
- config/: Configs.
- database/: DB schema/data.
- Docs/: Documentation.
- Modules/: Modules.
- node_modules/: NPM packages (ignored).
- public/: Static assets.
- resources/: Source assets.
- routes/: Routes.
- storage/: Storage (ignored).
- Website-Update/: Temp migration files.
```

## app/ (Models, Controllers, Providers)
```
app/Console/Kernel.php: Artisan scheduler.
app/Http/Controllers/ACController.php: AC unit management.
app/Http/Controllers/MasjidController.php: Masjid CRUD.
app/Http/Controllers/MonitoringController.php: Service order monitoring.
app/Http/Controllers/ProfileController.php: User profile.
app/Http/Controllers/ReportController.php: Reports.
app/Http/Controllers/TechnicianController.php: Technicians.
app/Http/Controllers/UserController.php: User management.
app/Http/Controllers/ViewerController.php: Viewer views.
app/Http/Controllers/WorkflowController.php: Workflow handling.
app/Models/AcUnit.php: AC unit model.
app/Models/Invoice.php: Invoice model.
app/Models/Masjid.php: Masjid model.
app/Models/ServiceDetail.php: Service details.
app/Models/ServiceOrder.php: Service order model (workflow relationships).
app/Models/TechnicianAssignment.php: Technician assignments.
app/Models/User.php: User model (roles).
app/Models/WorkflowStep.php: Workflow steps.
app/Providers/AppServiceProvider.php: App services.
app/Providers/ModuleServiceProvider.php: Modules.
app/Support/DebugBfd979Log.php: Debug logging.
app/helpers.php: Global helpers.
```

## bootstrap/ 
```
bootstrap/app.php: App bootstrap.
bootstrap/providers.php: Providers.
bootstrap/cache/: Cached configs (ignored).
```

## config/ (10+ files)
```
config/app.php: App config.
config/auth.php: Auth.
config/cache.php: Cache.
config/database.php: DB connections.
config/filesystems.php: Files.
config/logging.php: Logging (Papertrail).
config/mail.php: Mail.
config/modules.php: Modules.
config/queue.php: Queue.
config/session.php: Session.
config/services.php: Services.
```

## database/
```
database/factories/UserFactory.php: User factory.
database/migrations/*.php: Schema (users, masjids, ac_units, services, workflows ~15 files).
database/seeders/DatabaseSeeder.php: Main seeder.
database/seeders/AcUnitSeeder.php: AC seed.
database/seeders/MasjidSeeder.php: Masjid seed.
database/seeders/ServiceOrderSeeder.php: Orders seed.
database/seeders/data/: Seed data.
```

## Docs/ (10+ files)
```
Docs/ARCHITECTURE.md: System design.
Docs/UIUX_ARCHITECTURE_GUIDE.md: UI guide.
Docs/JS_DESIGN_INSTRUCTION.md: JS patterns.
Docs/AI*.md: AI instructions.
Docs/ADR-*.md: Decisions.
Docs/PROJECT_STRUCTURE.md: Folder structure.
```

## Modules/ (AcAnggota, AcMasjidMusholla, AcService, Inventory, FutureModule)
```
Each: Http/Controllers, resources/views, routes/web.php: Modular features.
```

## public/
```
public/index.php: Entry point.
public/.htaccess, robots.txt, favicon.ico: Web server.
public/css/*.css (~5): Styles (style, ui-overhaul, responsive).
public/js/*.js (~8): Client JS (app, dashboard, monitoring, workflow, GSAP/Lenis/AOS).
public/build/: Vite builds (ignored).
public/storage/: Symlink (ignored).
```

## resources/
```
resources/css/app.css: Base CSS.
resources/js/app.js: Core JS.
resources/js/animations.js, bootstrap.js, navigation.js: Utilities.
resources/views/*.blade.php: Templates (dashboard, monitoring, layouts, home, auth, users, profile, reports, technician, viewer).
resources/views/layouts/: Shared layouts/header/app.
resources/views/monitoring/: Workflow panels.
```

## routes/
```
routes/web.php: Web routes.
routes/console.php: Console.
```

## storage/ (ignored)
```
storage/logs/*.log: Logs.
storage/framework/: Sessions/cache/views.
```

## tests/
```
No test suite included in this repository.
```

**Total: ~250 files. Generated for onboarding/maintenance.**
