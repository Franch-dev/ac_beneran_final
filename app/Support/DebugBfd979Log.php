<?php

declare(strict_types=1);

namespace App\Support;

/**
 * NDJSON debug log (session bfd979). Writes under storage/logs so Laragon can create the file.
 */
final class DebugBfd979Log
{
    public static function write(string $hypothesisId, string $message, array $data = []): void
    {
        $path = storage_path('logs/debug-bfd979.log');
        $payload = json_encode([
            'sessionId' => 'bfd979',
            'hypothesisId' => $hypothesisId,
            'message' => $message,
            'data' => $data,
            'timestamp' => (int) round(microtime(true) * 1000),
        ], JSON_UNESCAPED_UNICODE);
        if ($payload !== false) {
            @file_put_contents($path, $payload."\n", FILE_APPEND | LOCK_EX);
        }
    }
}
