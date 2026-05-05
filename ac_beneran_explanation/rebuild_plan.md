# Rebuild Plan

## Step 1: Environment Setup
- Install PHP 8.2+, Composer, Node.js 18+, MySQL 5.7+.
- Create 3 MySQL databases: `main_platform`, `ac_service_db`, `inventory_db`.

## Step 2: Laravel Project Initialization
```bash
composer create-project laravel/laravel ac_beneran_final "12.*"
cd ac_beneran_final
npm install
```

## Step 3: Multi-Database Configuration
Update `.env`:
```env
DB_CONNECTION=main
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=main_platform
DB_USERNAME=root
DB_PASSWORD=

MAIN_DB_CONNECTION=mysql
MAIN_DB_HOST=127.0.0.1
MAIN_DB_PORT=3306
MAIN_DB_DATABASE=main_platform
MAIN_DB_USERNAME=root
MAIN_DB_PASSWORD=

AC_SERVICE_DB_CONNECTION=mysql
AC_SERVICE_DB_HOST=127.0.0.1
AC_SERVICE_DB_PORT=3306
AC_SERVICE_DB_DATABASE=ac_service_db
AC_SERVICE_DB_USERNAME=root
AC_SERVICE_DB_PASSWORD=

INVENTORY_DB_CONNECTION=mysql
INVENTORY_DB_HOST=127.0.0.1
INVENTORY_DB_PORT=3306
INVENTORY_DB_DATABASE=inventory_db
INVENTORY_DB_USERNAME=root
INVENTORY_DB_PASSWORD=
```

Update `config/database.php` to add connections:
```php
'main' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'main_platform'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
],
'ac_service' => [
    'driver' => 'mysql',
    'host' => env('AC_SERVICE_DB_HOST', '127.0.0.1'),
    'port' => env('AC_SERVICE_DB_PORT', '3306'),
    'database' => env('AC_SERVICE_DB_DATABASE', 'ac_service_db'),
    'username' => env('AC_SERVICE_DB_USERNAME', 'root'),
    'password' => env('AC_SERVICE_DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
],
'inventory' => [
    'driver' => 'mysql',
    'host' => env('INVENTORY_DB_HOST', '127.0.0.1'),
    'port' => env('INVENTORY_DB_PORT', '3306'),
    'database' => env('INVENTORY_DB_DATABASE', 'inventory_db'),
    'username' => env('INVENTORY_DB_USERNAME', 'root'),
    'password' => env('INVENTORY_DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
],
```

## Step 4: Modular Structure Setup
- Create `Modules/` directory with 5 modules: AcService, AcAnggota, AcMasjidMusholla, Inventory, FutureModule.
- Each module has `Controllers/`, `Routes/`, `Views/` subdirectories.

## Step 5: Database Migrations & Seeders
- Run `php artisan make:migration create_masjids_table --database=ac_service` for each table.
- Use the `database_schema.sql` as reference for table structures.
- Create seeders for test data: `php artisan make:seeder MasjidSeeder --database=ac_service`.

## Step 6: Model & Relationship Definition
- Create Eloquent models: `Masjid`, `AcUnit`, `ServiceOrder`, `Invoice`, `WorkflowStep`, `User` (extend Laravel's User).
- Define relationships:
  ```php
  // Masjid.php
  public function acUnits() { return $this->hasMany(AcUnit::class); }
  public function serviceOrders() { return $this->hasMany(ServiceOrder::class); }
  ```

## Step 7: Controller & Route Implementation
- Create controllers in `app/Http/Controllers/` and `Modules/*/Controllers/`.
- Define web routes in `routes/web.php`:
  ```php
  Route::get('/', [HomeController::class, 'index']);
  Route::middleware(['auth', 'role:frontdesk'])->group(function () {
      Route::post('/masjid', [MasjidController::class, 'store']);
  });
  ```

## Step 8: Frontend Setup
- Install dependencies: `npm install vite @tailwindcss/vite laravel-vite-plugin animejs aos gsap axios`.
- Configure `vite.config.js` as per project.
- Create `resources/css/app.css` (Tailwind directives) and `resources/js/app.js`.

## Step 9: Authentication & Role Middleware
- Set up Laravel Breeze custom: `composer require laravel/breeze --dev`.
- Create `CheckRole` middleware:
  ```php
  public function handle($request, Closure $next, ...$roles) {
      if (!auth()->user() || !in_array(auth()->user()->role, $roles)) {
          abort(403);
      }
      return $next($request);
  }
  ```
- Register middleware in `app/Http/Kernel.php`.

## Step 10: Scheduled Tasks Setup
- Add scheduled tasks to `app/Console/Kernel.php`:
  ```php
  $schedule->call(function () {
      ServiceOrder::whereDoesntHave('masjid')->delete();
  })->dailyAt('02:00');
  ```

## Step 11: Testing & Validation
- Run migrations: `php artisan db:setup`.
- Test user roles, service order flow, module pages.
- Build assets: `npm run build`.
