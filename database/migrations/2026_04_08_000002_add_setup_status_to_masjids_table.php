<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->table('masjids', function (Blueprint $table) {
            $table->string('setup_status', 40)->default('pending_ac')->after('phone_numbers');
            $table->timestamp('setup_completed_at')->nullable()->after('setup_status');
        });

        $connection = DB::connection('ac_service');
        $masjidIdsWithUnits = $connection->table('ac_units')
            ->select('masjid_id')
            ->distinct()
            ->pluck('masjid_id');

        if ($masjidIdsWithUnits->isNotEmpty()) {
            $connection->table('masjids')
                ->whereIn('id', $masjidIdsWithUnits)
                ->update([
                    'setup_status' => 'completed',
                    'setup_completed_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::connection('ac_service')->table('masjids', function (Blueprint $table) {
            $table->dropColumn(['setup_status', 'setup_completed_at']);
        });
    }
};
