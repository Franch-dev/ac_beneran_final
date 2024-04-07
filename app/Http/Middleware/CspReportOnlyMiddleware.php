<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CspReportOnlyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $scriptSrc = [
            "'self'",
            "'unsafe-inline'",
            'https://unpkg.com',
        ];

        $connectSrc = ["'self'"];

        if (app()->environment('local')) {
            $scriptSrc[] = "'unsafe-eval'";
            $scriptSrc[] = 'http://127.0.0.1:5173';
            $scriptSrc[] = 'http://localhost:5173';
            $connectSrc[] = 'http://127.0.0.1:5173';
            $connectSrc[] = 'http://localhost:5173';
            $connectSrc[] = 'ws://127.0.0.1:5173';
            $connectSrc[] = 'ws://localhost:5173';
        }

        $styleSrc = [
            "'self'",
            "'unsafe-inline'",
            'https://fonts.googleapis.com',
            'https://cdnjs.cloudflare.com',
            'https://unpkg.com',
        ];

        if (app()->environment('local')) {
            $styleSrc[] = 'http://127.0.0.1:5173';
            $styleSrc[] = 'http://localhost:5173';
        }

        $policy = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            'style-src '.implode(' ', $styleSrc),
            'script-src '.implode(' ', $scriptSrc),
            'connect-src '.implode(' ', $connectSrc),
            "upgrade-insecure-requests",
        ]);

        $response->headers->set('Content-Security-Policy-Report-Only', $policy);

        return $response;
    }
}

