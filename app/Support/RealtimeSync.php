<?php

namespace App\Support;

use App\Models\SyncEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RealtimeSync
{
    public static function afterCommit(string $type, array $attributes = [], string $connection = 'ac_service'): void
    {
        $payload = self::buildPayload($type, $attributes);
        $database = DB::connection($connection);
        $writeEvent = static function () use ($payload): void {
            SyncEvent::query()->create($payload);
        };

        if ($database->transactionLevel() > 0) {
            $database->afterCommit($writeEvent);

            return;
        }

        $writeEvent();
    }

    protected static function buildPayload(string $type, array $attributes): array
    {
        $actor = Auth::user();

        return [
            'type' => $type,
            'resource' => $attributes['resource'] ?? null,
            'resource_id' => $attributes['resource_id'] ?? null,
            'masjid_id' => $attributes['masjid_id'] ?? null,
            'service_order_id' => $attributes['service_order_id'] ?? null,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_role' => $actor?->role,
            'payload' => $attributes['payload'] ?? null,
        ];
    }
}
