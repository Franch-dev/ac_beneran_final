<?php

namespace App\Http\Controllers;

use App\Services\Database\DatabaseHealthService;
use App\Services\Skills\SkillCatalogSyncService;
use App\Services\Skills\SkillLoaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackendOpsController extends Controller
{
    public function dbHealth(DatabaseHealthService $healthService): JsonResponse
    {
        $connections = $healthService->checkConnections();
        $ok = collect($connections)->every(fn (array $info) => $info['ok'] === true);

        return response()->json([
            'ok' => $ok,
            'connections' => $connections,
            'checked_at' => now()->toISOString(),
        ], $ok ? 200 : 503);
    }

    public function listSkills(SkillLoaderService $loader): JsonResponse
    {
        return response()->json([
            'skills' => $loader->listSkillManifests(),
        ]);
    }

    public function relevantSkills(Request $request, SkillLoaderService $loader): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:2000'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        return response()->json([
            'matches' => $loader->findRelevantSkills($validated['query'], (int) ($validated['limit'] ?? 8)),
        ]);
    }

    public function syncSkills(SkillCatalogSyncService $syncService): JsonResponse
    {
        return response()->json([
            'result' => $syncService->syncToDatabase(),
            'synced_at' => now()->toISOString(),
        ]);
    }
}

