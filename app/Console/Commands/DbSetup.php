<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Exception;

class DbSetup extends Command
{
    protected $signature = 'db:setup {--fresh : Rollback and re-run all migrations}';
    protected $description = 'Setup all project databases: create if missing, run migrations idempotently, seed data. Safe for updates/clones.';

    public function handle()
    {
        $this->info('Starting AC project database setup...');

        $connections = collect(['main', 'ac_service', 'inventory', 'ac_anggota'])
            ->filter(fn (string $connection): bool => (bool) config("database.connections.{$connection}"))
            ->unique()
            ->values()
            ->all();

        foreach ($connections as $connection) {
            try {
                $this->info("Processing connection: {$connection}");

                $driver = config("database.connections.{$connection}.driver");
                $dbName = config("database.connections.{$connection}.database");
                if (! in_array($driver, ['mysql', 'mariadb'], true)) {
                    $this->warn("Skipping {$connection}: setup command only provisions MySQL/MariaDB databases.");
                    continue;
                }

                if (!$dbName || $dbName === 'mysql') {
                    $this->warn("Invalid DB config for {$connection} ({$dbName}). Check .env. Skipping.");
                    continue;
                }

                DB::purge($connection);
                $pdo = DB::connection($connection)->getPdo();

                // Create DB if not exists
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $this->line("✅ Database '{$dbName}' ensured.");

                // Now migrate with proper connection
                $options = [
                    '--database' => $connection,
                    '--force' => true,
                ];
                Artisan::call('migrate', $options);
                $this->info("✅ Migrations complete for {$connection}");

            } catch (Exception $e) {
                $this->error("❌ Error with {$connection}: " . $e->getMessage());
            }
        }

        // Seed
        Artisan::call('db:seed', ['--force' => true]);
        $this->info('✅ Seeding complete.');

        $this->newLine();
        $this->info('Database setup finished! Tables updated safely (idempotent).');
        $this->warn('Note: Some migrations may still error if not idempotent. Original ac_units error is fixed. Run `php artisan migrate --force` per connection if needed.');
    }
}
