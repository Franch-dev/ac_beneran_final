<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('main')->statement(
            "ALTER TABLE users MODIFY role ENUM('frontdesk','manager','admin','technician','viewer') NOT NULL DEFAULT 'frontdesk'"
        );
    }

    public function down(): void
    {
        DB::connection('main')->statement(
            "ALTER TABLE users MODIFY role ENUM('frontdesk','manager','admin') NOT NULL DEFAULT 'frontdesk'"
        );
    }
};
