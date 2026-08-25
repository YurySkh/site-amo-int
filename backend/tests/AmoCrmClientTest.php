<?php

declare(strict_types=1);

use App\AmoCrm\AmoCrmClient;
use App\AmoCrm\AmoCrmConfig;
use App\AmoCrm\AmoCrmException;
use App\Http\HttpClient;
use App\Http\HttpResponse;

require dirname(__DIR__) . '/bootstrap.php';

final class FakeHttpClient implements HttpClient
{
    /** @var array{method: string, url: string, headers: array<string>, body: string}|null */
    public ?array $request = null;

    public function __construct(private readonly HttpResponse $response)
    {
    }

    public function request(string $method, string $url, array $headers, string $body): HttpResponse
    {
        $this->request = compact('method', 'url', 'headers', 'body');

        return $this->response;
    }
}

$passed = 0;
$failed = 0;

/**
 * @param callable(): void $test
 */
function test(string $name, callable $test): void
{
    global $passed, $failed;

    try {
        $test();
        $passed++;
        echo "PASS {$name}\n";
    } catch (Throwable $exception) {
        $failed++;
        echo 'FAIL ' . $name . ': ' . $exception->getMessage() . PHP_EOL;
    }
}

function assertSameValue(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            'Expected %s, got %s.',
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

test('creates a complex lead and returns its ID', static function (): void {
    $httpClient = new FakeHttpClient(new HttpResponse(
        200,
        '{"_embedded":{"leads":[{"id":54886}]}}',
    ));
    $client = new AmoCrmClient(
        new AmoCrmConfig('https://example.amocrm.ru', 'secret-token'),
        $httpClient,
    );

    $leadId = $client->createLeadWithContact(['name' => 'Website lead']);

    assertSameValue(54886, $leadId);
    assertSameValue('POST', $httpClient->request['method']);
    assertSameValue('https://example.amocrm.ru/api/v4/leads/complex', $httpClient->request['url']);
    assertSameValue(true, in_array('Authorization: Bearer secret-token', $httpClient->request['headers'], true));
    assertSameValue('[{"name":"Website lead"}]', $httpClient->request['body']);
});

test('rejects a non-HTTPS account URL', static function (): void {
    try {
        new AmoCrmConfig('http://example.amocrm.ru', 'secret-token');
    } catch (AmoCrmException) {
        return;
    }

    throw new RuntimeException('Expected configuration to fail.');
});

test('reports a rejected access token', static function (): void {
    $client = new AmoCrmClient(
        new AmoCrmConfig('https://example.amocrm.ru', 'secret-token'),
        new FakeHttpClient(new HttpResponse(401, '{}')),
    );

    try {
        $client->createLeadWithContact([]);
    } catch (AmoCrmException $exception) {
        assertSameValue('amoCRM rejected the access token.', $exception->getMessage());
        return;
    }

    throw new RuntimeException('Expected amoCRM request to fail.');
});

test('rejects an invalid success response', static function (): void {
    $client = new AmoCrmClient(
        new AmoCrmConfig('https://example.amocrm.ru', 'secret-token'),
        new FakeHttpClient(new HttpResponse(200, '{"unexpected":true}')),
    );

    try {
        $client->createLeadWithContact([]);
    } catch (AmoCrmException $exception) {
        assertSameValue('amoCRM response does not contain a lead ID.', $exception->getMessage());
        return;
    }

    throw new RuntimeException('Expected amoCRM response parsing to fail.');
});

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
