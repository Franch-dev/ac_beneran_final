<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Simply use raw SQL for each column that's missing
        $this->addColumnRaw('ac_service', 'anggotas', 'type', 'VARCHAR(255) DEFAULT "anggota"');
        $this->addColumnRaw('ac_service', 'anggotas', 'member_code', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'registered_at', 'DATE NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'gender', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'family_card_number', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'national_id_number', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'birth_date', 'DATE NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'family_role', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'membership_status', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'phone_number', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'whatsapp_number', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'email', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'location', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'street', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'house_number', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'rt', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'rw', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'subdistrict', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'district', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'city', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'province', 'VARCHAR(255) NULL');
        $this->addColumnRaw('ac_service', 'anggotas', 'contact_name', 'VARCHAR(255) NULL');
        
        // Add to ac_units
        $this->addColumnRaw('ac_service', 'ac_units', 'pk_type', 'ENUM("1PK","2PK","5PK") NOT NULL');
        $this->addColumnRaw('ac_service', 'ac_units', 'brand', 'VARCHAR(255) NOT NULL');
        $this->addColumnRaw('ac_service', 'ac_units', 'quantity', 'INT NOT NULL');
        $this->addColumnRaw('ac_service', 'ac_units', 'last_service_date', 'DATE NULL');
        
        echo "Columns added!\n";
    }

    private function addColumnRaw(string $connection, string $table, string $column, string $definition): void
    {
        try {
            $columns = DB::connection($connection)->getSchemaBuilder()->getColumnListing($table);
            if (!in_array($column, $columns)) {
                DB::connection($connection)->statement("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
                echo "Added {$column} to {$table}\n";
            }
        } catch (\Exception $e) {
            // Skip if column already exists
        }
    }
};