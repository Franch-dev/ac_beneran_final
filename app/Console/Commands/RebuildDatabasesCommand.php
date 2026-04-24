<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class RebuildDatabasesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:rebuild';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean all database connections and rebuild from scratch with seeders.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('This will completely DESTROY all data across all configured databases. Are you sure?')) {
            $this->info('Database rebuild cancelled.');
            return;
        }

        $connections = ['main', 'ac_service', 'inventory'];

        foreach ($connections as $connectionName) {
            $this->info("Dropping all tables in connection: {$connectionName}...");
            $this->dropAllTables($connectionName);
        }

        $this->info("All database tables dropped cleanly.");
        $this->info("Running migrate:fresh...");
        
        $this->call('migrate:fresh', [
            '--force' => true,
        ]);

        $this->info("Running seeders...");
        $this->call('db:seed', [
            '--force' => true,
        ]);

        $this->info("Database successfully rebuilt and seeded!");
    }

    private function dropAllTables(string $connectionName): void
    {
        try {
            $connection = DB::connection($connectionName);
            $schema = $connection->getSchemaBuilder();
            
            $schema->disableForeignKeyConstraints();
            
            // Getting all table names based on DB driver. Assuming MySQL here.
            $tables = $connection->select('SHOW TABLES');
            
            foreach ($tables as $table) {
                $tableName = current((array)$table);
                $schema->drop($tableName);
            }
            
            $schema->enableForeignKeyConstraints();
        } catch (\Exception $e) {
            $this->warn("Could not clear connection {$connectionName}: " . $e->getMessage());
        }
    }
}
