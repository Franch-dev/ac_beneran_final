<?php

namespace App\Services\Skills;

use Illuminate\Support\Facades\DB;

class SkillCatalogSyncService
{
    public function __construct(
        protected SkillLoaderService $loader
    ) {
    }

    /**
     * @return array{synced:int,deleted:int}
     */
    public function syncToDatabase(): array
    {
        $manifests = $this->loader->listSkillManifests();
        $activePaths = [];

        foreach ($manifests as $manifest) {
            $path = (string) ($manifest['path'] ?? '');
            if ($path === '') {
                continue;
            }

            $loaded = $this->loader->loadSkill($path);
            $activePaths[] = $loaded['path'];

            DB::connection('main')->table('skill_catalogs')->updateOrInsert(
                ['path' => $loaded['path']],
                [
                    'checksum' => $loaded['checksum'],
                    'size_bytes' => $loaded['size'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $deleted = DB::connection('main')
            ->table('skill_catalogs')
            ->when($activePaths !== [], fn ($q) => $q->whereNotIn('path', $activePaths))
            ->when($activePaths === [], fn ($q) => $q)
            ->delete();

        return [
            'synced' => count($activePaths),
            'deleted' => $deleted,
        ];
    }
}

