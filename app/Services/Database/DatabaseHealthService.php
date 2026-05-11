<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseHealthService
{
    /**
     * @return array<string, array{ok:bool,error:?string,database:?string}>
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
                    'database' => DB::connection($name)->getDatabaseName(),
                ];
            } catch (Throwable $e) {
                $result[$name] = [
                    'ok' => false,
                    'error' => $e->getMessage(),
                    'database' => null,
                ];
            }
        }

        return $result;
    }
}

