<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SqlDateExpressions
{
    public static function daysSince(string $column, ?string $connection = null): string
    {
        $driver = DB::connection($connection)->getDriverName();

        return match ($driver) {
            'sqlite' => "CAST((julianday('now') - julianday({$column})) AS INTEGER)",
            default => "DATEDIFF(CURDATE(), {$column})",
        };
    }

    public static function monthBucket(string $column, ?string $connection = null): string
    {
        $driver = DB::connection($connection)->getDriverName();

        return match ($driver) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}

