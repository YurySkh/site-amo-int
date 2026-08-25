<?php

declare(strict_types=1);

namespace App\Http;

interface HttpClient
{
    /**
     * @param array<string> $headers
     */
    public function request(
        string $method,
        string $url,
        array $headers,
        string $body,
    ): HttpResponse;
}
