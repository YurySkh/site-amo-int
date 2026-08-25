<?php

declare(strict_types=1);

use App\AmoCrm\AmoCrmClient;
use App\AmoCrm\AmoCrmConfig;
use App\AmoCrm\AmoCrmException;
use App\AmoCrm\AmoCrmPayloadFactory;
use App\Http\ApiException;
use App\Http\Cors;
use App\Http\CurlHttpClient;
use App\Http\JsonRequest;
use App\Http\JsonResponse;
use App\Validation\LeadRequestValidator;

require dirname(__DIR__) . '/bootstrap.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

try {
    Cors::apply($_SERVER['HTTP_ORIGIN'] ?? null);

    if ($method === 'OPTIONS') {
        JsonResponse::send(null, 204);
    }

    if ($method === 'GET' && $path === '/health') {
        JsonResponse::send([
            'success' => true,
            'status' => 'ok',
        ]);
    }

    if ($method === 'POST' && $path === '/api/leads') {
        $lead = LeadRequestValidator::validate(JsonRequest::body());

        if (!AmoCrmConfig::isConfigured()) {
            JsonResponse::send([
                'success' => true,
                'message' => 'Payload validated successfully.',
                'mode' => 'validation_only',
                'meta' => [
                    'timeOnSiteOver30' => $lead->timeOnSiteOver30,
                    'hasRoistatVisit' => $lead->roistatVisit !== '',
                ],
            ]);
        }

        $amoCrmClient = new AmoCrmClient(
            AmoCrmConfig::fromEnvironment(),
            new CurlHttpClient(),
        );
        $leadId = $amoCrmClient->createLeadWithContact(
            (new AmoCrmPayloadFactory())->create($lead),
        );

        JsonResponse::send([
            'success' => true,
            'message' => 'Lead created successfully.',
            'data' => [
                'leadId' => $leadId,
            ],
        ], 201);
    }

    throw new ApiException(404, 'Endpoint not found.');
} catch (ApiException $exception) {
    JsonResponse::send([
        'success' => false,
        'message' => $exception->getMessage(),
        'errors' => $exception->errors(),
    ], $exception->statusCode());
} catch (AmoCrmException $exception) {
    error_log('amoCRM integration error: ' . $exception->getMessage());

    JsonResponse::send([
        'success' => false,
        'message' => 'Unable to create the lead in amoCRM.',
    ], 502);
} catch (Throwable $exception) {
    error_log($exception->__toString());

    JsonResponse::send([
        'success' => false,
        'message' => 'Internal server error.',
    ], 500);
}
