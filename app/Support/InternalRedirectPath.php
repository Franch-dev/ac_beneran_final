<?php

namespace App\Support;

final class InternalRedirectPath
{
    public static function normalize(?string $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $path = trim($path);

        if ($path === '' || ! str_starts_with($path, '/')) {
            return null;
        }

        if (
            str_starts_with($path, '//')
            || str_contains($path, '://')
            || str_contains($path, "\0")
        ) {
            return null;
        }

        return $path;
    }
}
