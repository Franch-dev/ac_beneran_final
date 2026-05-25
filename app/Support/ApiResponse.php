<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Standardized JSON response helper.
 *
 * All responses preserve the exact same JSON shape as the current code:
 *   Success: { success: true, ...extraKeys, message: "..." }
 *   Error:   { success: false, message: "...", errors: {...} }
 *   Snapshot: { html: "..." }
 *   Raw:     direct model/collection dump
 */
class ApiResponse
{
    /**
     * Success response. Extra data is merged at the top level alongside "success".
     *
     * @param  array       $extra      Associative array merged into response.
     * @param  string|null $message    Optional success message.
     * @param  int         $statusCode HTTP status code (default 200).
     */
    public static function success(array $extra = [], ?string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = array_merge(['success' => true], $extra);

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Resource created (201).
     */
    public static function created(array $extra = [], ?string $message = null): JsonResponse
    {
        return static::success($extra, $message, 201);
    }

    /**
     * Error response.
     *
     * @param  string      $message    Error description.
     * @param  int         $statusCode HTTP status code (default 422).
     * @param  mixed|null  $errors     Optional validation error details.
     */
    public static function error(string $message, int $statusCode = 422, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Forbidden (403) shortcut.
     */
    public static function forbidden(string $message = 'Akses tidak diizinkan.'): JsonResponse
    {
        return static::error($message, 403);
    }

    /**
     * Not found (404) shortcut.
     */
    public static function notFound(string $message = 'Resource tidak ditemukan.'): JsonResponse
    {
        return static::error($message, 404);
    }

    /**
     * Snapshot response for AJAX partial page refreshes — shape: { html: "..." }.
     */
    public static function snapshot(string $html): JsonResponse
    {
        return response()->json(['html' => $html]);
    }

    /**
     * Raw data dump — for direct model/collection responses
     * that frontend expects without a wrapping envelope.
     */
    public static function raw(mixed $data, int $statusCode = 200): JsonResponse
    {
        return response()->json($data, $statusCode);
    }
}
