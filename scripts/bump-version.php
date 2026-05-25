<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/bump-version.php <commit-message-file>\n");
    exit(1);
}

$commitMessageFile = $argv[1];
if (!is_file($commitMessageFile)) {
    fwrite(STDERR, "Commit message file not found: {$commitMessageFile}\n");
    exit(1);
}

$message = trim((string) file_get_contents($commitMessageFile));
if ($message === '') {
    fwrite(STDERR, "Empty commit message.\n");
    exit(1);
}

$lines = preg_split('/\R/', $message) ?: [];
$subject = trim((string) ($lines[0] ?? ''));

// Allow merge/revert without bumping to avoid blocking maintenance workflows.
if (preg_match('/^(Merge|Revert)\b/i', $subject) === 1) {
    exit(0);
}

$matches = [];
$isConventional = preg_match(
    '/^(?<type>[a-z]+)(\([a-z0-9._-]+\))?(?<breaking>!)?:\s.+$/',
    $subject,
    $matches
);

if ($isConventional !== 1) {
    fwrite(STDERR, "Invalid Conventional Commit format.\n");
    fwrite(STDERR, "Expected: type(scope?): subject\n");
    exit(1);
}

$type = strtolower((string) ($matches['type'] ?? ''));
$hasBreakingMarker = (($matches['breaking'] ?? '') === '!');
$hasBreakingFooter = (bool) preg_match('/^BREAKING CHANGE:/mi', $message);

$level = 'patch';
if ($hasBreakingMarker || $hasBreakingFooter) {
    $level = 'major';
} elseif ($type === 'feat') {
    $level = 'minor';
}

$versionFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'sitemap.php';
if (!is_file($versionFile)) {
    fwrite(STDERR, "Version source file not found: {$versionFile}\n");
    exit(1);
}

$content = (string) file_get_contents($versionFile);
$versionMatch = [];
if (preg_match("/'version'\\s*=>\\s*'(?<major>\\d+)\\.(?<minor>\\d+)\\.(?<patch>\\d+)'/", $content, $versionMatch) !== 1) {
    fwrite(STDERR, "Unable to find semantic version in config/sitemap.php\n");
    exit(1);
}

$major = (int) $versionMatch['major'];
$minor = (int) $versionMatch['minor'];
$patch = (int) $versionMatch['patch'];

if ($level === 'major') {
    $major++;
    $minor = 0;
    $patch = 0;
} elseif ($level === 'minor') {
    $minor++;
    $patch = 0;
} else {
    $patch++;
}

$newVersion = "{$major}.{$minor}.{$patch}";
$updated = preg_replace(
    "/('version'\\s*=>\\s*')\\d+\\.\\d+\\.\\d+(')/",
    '$1' . $newVersion . '$2',
    $content,
    1
);

if (!is_string($updated)) {
    fwrite(STDERR, "Failed to update version string.\n");
    exit(1);
}

if ($updated !== $content) {
    file_put_contents($versionFile, $updated);
}

fwrite(STDOUT, "Bumped version to {$newVersion} ({$level})\n");
exit(0);
