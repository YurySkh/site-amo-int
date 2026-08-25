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

        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);

        $responseBody = curl_exec($handle);

        if ($responseBody === false) {
            $error = curl_error($handle);
            curl_close($handle);
            throw new AmoCrmException('amoCRM request failed: ' . $error);
        }

        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        return new HttpResponse($statusCode, $responseBody);
    }
}
