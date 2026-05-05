<?php

namespace App\Utilities;

class TextSanitizer {
    public static function sanitize(?string $text): string {
        if (!$text) return '';
        // Remove HTML tags
        $sanitized = strip_tags($text);

        // Remove image URLs (http/https) to prevent image loads in UI
        $sanitized = preg_replace('/https?:\/\/[^\s]+\.(png|jpg|jpeg|gif|webp|svg)/i', '', $sanitized);

        // Remove data URIs for images
        $sanitized = preg_replace('/data:image\/[^;]+;base64,[A-Za-z0-9+\/=]+/', '', $sanitized);

        // Remove any remaining URL patterns that might reference images
        $sanitized = preg_replace('/\.(png|jpg|jpeg|gif|webp|svg)\b/i', '', $sanitized);

        return trim($sanitized);
    }
}
