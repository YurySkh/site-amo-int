<?php

declare(strict_types=1);

namespace App\Http;

use App\AmoCrm\AmoCrmException;

final class CurlHttpClient implements HttpClient
{
    public function request(
        string $method,
        string $url,
        array $headers,
        string $body,
    ): HttpResponse {
        $handle = curl_init($url);

        if ($handle === false) {
            throw new AmoCrmException('Failed to initialize an amoCRM request.');
        }

        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ];

        if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) {
            $options[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
        }

        curl_setopt_array($handle, $options);

        $responseBody = curl_exec($handle);

        if ($responseBody === false) {
            $error = curl_error($handle);
            throw new AmoCrmException('amoCRM request failed: ' . $error);
        }

        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

        return new HttpResponse($statusCode, $responseBody);
    }
}
