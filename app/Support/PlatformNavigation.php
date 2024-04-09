<?php

namespace App\Support;

final class PlatformNavigation
{
    public static function homeUrl(?string $fragment = null): string
    {
        return static::platformUrl('/', $fragment);
    }

    public static function catalogUrl(): string
    {
        return static::homeUrl('katalog');
    }

    public static function platformUrl(string $path = '/', ?string $fragment = null): string
    {
        $baseUrl = rtrim((string) config('app.url', url('/')), '/');
        $normalizedPath = '/'.ltrim($path, '/');
        $url = $normalizedPath === '/' ? $baseUrl : $baseUrl.$normalizedPath;

        if (is_string($fragment) && $fragment !== '') {
            $url .= '#'.ltrim($fragment, '#');
        }

        return $url;
    }

    public static function loginUrl(?string $redirect = null): string
    {
        $parameters = [];
        $normalizedRedirect = InternalRedirectPath::normalize($redirect);

        if ($normalizedRedirect !== null) {
            $parameters['redirect'] = $normalizedRedirect;
        }

        return route('login', $parameters, false);
    }
}
