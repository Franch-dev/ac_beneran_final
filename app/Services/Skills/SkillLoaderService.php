<?php

namespace App\Services\Skills;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class SkillLoaderService
{
    public function __construct(
        protected Filesystem $filesystem
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSkillManifests(): array
    {
        $root = $this->skillsRoot();

        if (! $this->filesystem->exists($root)) {
            return [];
        }

        $files = [];
        foreach ($this->filesystem->allFiles($root) as $file) {
            $absolutePath = $file->getPathname();
            if (! $this->isAllowedSkillFile($absolutePath)) {
                continue;
            }

            $relativePath = str_replace('\\', '/', Str::after($absolutePath, $root.DIRECTORY_SEPARATOR));
            $files[] = [
                'path' => $relativePath,
                'size' => $file->getSize(),
                'last_modified_at' => date('c', $file->getMTime()),
            ];
        }

        return $files;
    }

    /**
     * @return array{path:string,size:int,checksum:string,content:string}
     */
    public function loadSkill(string $relativePath): array
    {
        $absolutePath = $this->resolveSafePath($relativePath);
        $size = $this->filesystem->size($absolutePath);
        $maxSize = (int) config('skills.max_file_size', 2 * 1024 * 1024);

        if ($size > $maxSize) {
            throw new RuntimeException("Skill file too large: {$relativePath}");
        }

        $content = (string) $this->filesystem->get($absolutePath);

        return [
            'path' => str_replace('\\', '/', $relativePath),
            'size' => $size,
            'checksum' => hash('sha256', $content),
            'content' => $content,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findRelevantSkills(string $query, int $limit = 8): array
    {
        $terms = collect(preg_split('/\W+/u', Str::lower($query)) ?: [])
            ->filter()
            ->unique()
            ->values();

        if ($terms->isEmpty()) {
            return [];
        }

        $snippetLength = (int) config('skills.snippet_length', 1200);

        $scored = [];
        foreach ($this->listSkillManifests() as $manifest) {
            $path = Arr::get($manifest, 'path');
            if (! is_string($path) || $path === '') {
                continue;
            }

            $loaded = $this->loadSkill($path);
            $haystack = Str::lower(Str::limit($loaded['content'], $snippetLength, ''));
            $score = $terms->reduce(
                fn (int $carry, string $term) => $carry + (Str::contains($haystack, $term) ? 1 : 0),
                0
            );

            if ($score <= 0) {
                continue;
            }

            $scored[] = [
                'path' => $loaded['path'],
                'checksum' => $loaded['checksum'],
                'score' => $score,
                'size' => $loaded['size'],
                'snippet' => Str::limit($loaded['content'], 280),
            ];
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, max(1, $limit));
    }

    protected function skillsRoot(): string
    {
        return rtrim((string) config('skills.path', base_path('skills')), DIRECTORY_SEPARATOR);
    }

    protected function isAllowedSkillFile(string $absolutePath): bool
    {
        $allowed = collect(config('skills.allowed_extensions', []))
            ->map(fn ($ext) => Str::lower((string) $ext))
            ->all();

        return in_array(Str::lower(pathinfo($absolutePath, PATHINFO_EXTENSION)), $allowed, true);
    }

    protected function resolveSafePath(string $relativePath): string
    {
        $root = realpath($this->skillsRoot());
        if ($root === false) {
            throw new RuntimeException('Skills path does not exist.');
        }

        $target = realpath($root.DIRECTORY_SEPARATOR.ltrim($relativePath, '/\\'));
        if ($target === false) {
            throw new RuntimeException("Skill file not found: {$relativePath}");
        }

        $normalizedRoot = str_replace('\\', '/', $root);
        $normalizedTarget = str_replace('\\', '/', $target);
        if (! Str::startsWith($normalizedTarget, $normalizedRoot.'/') && $normalizedTarget !== $normalizedRoot) {
            throw new RuntimeException('Illegal skill file path.');
        }

        if (is_link($target)) {
            throw new RuntimeException('Symlink skill files are not allowed.');
        }

        if (! $this->isAllowedSkillFile($target)) {
            throw new RuntimeException('Unsupported skill file extension.');
        }

        return $target;
    }
}

