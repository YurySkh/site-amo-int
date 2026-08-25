<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

final class ApiException extends RuntimeException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        private readonly int $responseStatusCode,
        string $message,
        private readonly array $responseErrors = [],
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->responseStatusCode;
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->responseErrors;
    }
}
