<?php

declare(strict_types=1);

namespace App\AmoCrm;

final readonly class AmoCrmConfig
{
    public function __construct(
        public string $baseUrl,
        public string $accessToken,
    ) {
        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        $scheme = parse_url($this->baseUrl, PHP_URL_SCHEME);

        if ($scheme !== 'https' || !is_string($host) || !str_ends_with($host, '.amocrm.ru')) {
            throw new AmoCrmException('AMOCRM_BASE_URL must be an HTTPS amoCRM account URL.');
        }

        if (trim($this->accessToken) === '') {
            throw new AmoCrmException('AMOCRM_ACCESS_TOKEN must not be empty.');
        }
    }

    public static function isConfigured(): bool
    {
        return self::environmentValue('AMOCRM_BASE_URL') !== ''
            && self::environmentValue('AMOCRM_ACCESS_TOKEN') !== '';
    }

    public static function fromEnvironment(): self
    {
        if (!self::isConfigured()) {
            throw new AmoCrmException('amoCRM integration is not configured.');
        }

        return new self(
            baseUrl: rtrim(self::environmentValue('AMOCRM_BASE_URL'), '/'),
            accessToken: self::environmentValue('AMOCRM_ACCESS_TOKEN'),
        );
    }

    private static function environmentValue(string $name): string
    {
        $value = getenv($name);

        return is_string($value) ? trim($value) : '';
    }
}
