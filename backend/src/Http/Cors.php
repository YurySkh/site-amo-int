<?php

declare(strict_types=1);

namespace App\Http;

final class Cors
{
    public static function apply(?string $requestOrigin): void
    {
        $allowedOrigin = getenv('ALLOWED_ORIGIN') ?: 'http://127.0.0.1:4173';

        header('Vary: Origin');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Max-Age: 600');

        if ($requestOrigin === null || $requestOrigin === '') {
            return;
        }

        if (!hash_equals($allowedOrigin, $requestOrigin)) {
            throw new ApiException(403, 'Origin is not allowed.');
        }

        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    }
}
