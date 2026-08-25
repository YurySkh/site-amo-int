<?php

declare(strict_types=1);

namespace App\AmoCrm;

use App\Http\HttpClient;
use JsonException;

final readonly class AmoCrmClient
{
    public function __construct(
        private AmoCrmConfig $config,
        private HttpClient $httpClient,
    ) {
    }

    /**
     * @param array<string, mixed> $leadPayload
     */
    public function createLeadWithContact(array $leadPayload): int
    {
        try {
            $requestBody = json_encode(
                [$leadPayload],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException $exception) {
            throw new AmoCrmException('Failed to encode the amoCRM request.', previous: $exception);
        }

        $response = $this->httpClient->request(
            method: 'POST',
            url: $this->config->baseUrl . '/api/v4/leads/complex',
            headers: [
                'Authorization: Bearer ' . $this->config->accessToken,
                'Content-Type: application/json',
                'Accept: application/hal+json',
            ],
            body: $requestBody,
        );

        if ($response->statusCode === 401) {
            throw new AmoCrmException('amoCRM rejected the access token.');
        }

        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            throw new AmoCrmException(sprintf(
                'amoCRM returned an unexpected HTTP %d response.',
                $response->statusCode,
            ));
        }

        try {
            $responseData = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AmoCrmException('amoCRM returned invalid JSON.', previous: $exception);
        }

        $leadId = $responseData[0]['id'] ?? null;

        if (!is_int($leadId)) {
            throw new AmoCrmException('amoCRM response does not contain a lead ID.');
        }

        return $leadId;
    }
}
