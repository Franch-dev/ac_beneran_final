<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DatabaseHealthService
{
    /**
     * @return array<string, array{ok:bool,error:?string}>
     */
    public function checkConnections(): array
    {
        $connections = ['main', 'ac_service', 'ac_anggota', 'inventory'];
        $result = [];

        foreach ($connections as $name) {
            try {
                DB::connection($name)->select('SELECT 1');
                $result[$name] = [
                    'ok' => true,
                    'error' => null,
                ];
            } catch (Throwable $e) {
                Log::warning('database_health_check_failed', [
                    'connection' => $name,
                    'exception' => $e::class,
                ]);

                $result[$name] = [
                    'ok' => false,
                    'error' => 'Connection check failed.',
                ];
            }
        }

        return $result;
    }
}
