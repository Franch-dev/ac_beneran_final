<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = Schema::connection('main');
        $driver = DB::connection('main')->getDriverName();

        $connection->table('users', function (Blueprint $table) use ($driver): void {
            if ($driver === 'sqlite') {
                $table->string('role')->default('frontdesk');
                return;
            }

            $table->enum('role', ['frontdesk', 'manager', 'admin', 'technician', 'viewer'])
                ->default('frontdesk')
                ->after('password');
        });
    }

    public function down(): void
    {
        Schema::connection('main')->table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
