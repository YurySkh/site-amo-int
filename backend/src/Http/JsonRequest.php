<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;
use stdClass;

final class JsonRequest
{
    /**
     * @return array<string, mixed>
     */
    public static function body(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (!str_contains(strtolower($contentType), 'application/json')) {
            throw new ApiException(415, 'Content-Type must be application/json.');
        }

        $rawBody = file_get_contents('php://input');

        if ($rawBody === false || trim($rawBody) === '') {
            throw new ApiException(400, 'Request body must not be empty.');
        }

        try {
            $decoded = json_decode($rawBody, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ApiException(400, 'Request body contains invalid JSON.');
        }

        if (!$decoded instanceof stdClass) {
            throw new ApiException(400, 'Request body must be a JSON object.');
        }

        return (array) $decoded;
    }
}
