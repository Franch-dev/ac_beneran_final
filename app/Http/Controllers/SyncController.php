<?php

namespace App\Http\Controllers;

use App\Models\SyncEvent;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SyncController extends Controller
{
    public function stream(Request $request): StreamedResponse
    {
        $lastEventId = max(
            (int) ($request->header('Last-Event-ID') ?: $request->query('last_event_id', 0)),
            0
        );

        return response()->stream(function () use ($lastEventId): void {
            $cursor = $lastEventId;
            $startedAt = microtime(true);

            ignore_user_abort(true);
            @set_time_limit(0);

            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            echo "retry: 2000\n";
            echo ": connected\n\n";
            flush();

            while (! connection_aborted() && (microtime(true) - $startedAt) < 25) {
                $events = SyncEvent::query()
                    ->where('id', '>', $cursor)
                    ->orderBy('id')
                    ->limit(25)
                    ->get();

                if ($events->isEmpty()) {
                    echo ": heartbeat\n\n";
                    flush();
                    usleep(500000);
                    continue;
                }

                foreach ($events as $event) {
                    $cursor = $event->id;

                    echo "id: {$event->id}\n";
                    echo "event: sync\n";
                    echo 'data: '.json_encode([
                        'id' => $event->id,
                        'type' => $event->type,
                        'resource' => $event->resource,
                        'resource_id' => $event->resource_id,
                        'masjid_id' => $event->masjid_id,
                        'service_order_id' => $event->service_order_id,
                        'actor_id' => $event->actor_id,
                        'actor_name' => $event->actor_name,
                        'actor_role' => $event->actor_role,
                        'payload' => $event->payload,
                        'created_at' => optional($event->created_at)->toIso8601String(),
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n\n";
                    flush();
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
