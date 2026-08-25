<?php

declare(strict_types=1);

use App\Http\ApiException;
use App\Validation\LeadRequestValidator;

require dirname(__DIR__) . '/bootstrap.php';

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

/**
 * @param array<string, mixed> $payload
 * @param array<string> $expectedFields
 */
function assertValidationErrors(array $payload, array $expectedFields): void
{
    try {
        LeadRequestValidator::validate($payload);
    } catch (ApiException $exception) {
        assertSameValue(422, $exception->statusCode());

        foreach ($expectedFields as $field) {
            if (!array_key_exists($field, $exception->errors())) {
                throw new RuntimeException("Missing validation error for {$field}.");
            }
        }

        return;
    }

    throw new RuntimeException('Expected validation to fail.');
}

test('normalizes a valid payload', static function (): void {
    $lead = LeadRequestValidator::validate([
        'name' => '  Анна  ',
        'email' => '  anna@example.com ',
        'phone' => '0012345',
        'price' => '00001250',
        'timeOnSiteOver30' => true,
        'roistatVisit' => 'visit-42',
    ]);

    assertSameValue([
        'name' => 'Анна',
        'email' => 'anna@example.com',
        'phone' => '0012345',
        'price' => 1250,
        'timeOnSiteOver30' => true,
        'roistatVisit' => 'visit-42',
    ], $lead->toArray());
});

test('uses defaults for optional values', static function (): void {
    $lead = LeadRequestValidator::validate([
        'phone' => '12345',
        'price' => '',
    ]);

    assertSameValue('', $lead->name);
    assertSameValue('', $lead->email);
    assertSameValue(0, $lead->price);
    assertSameValue(false, $lead->timeOnSiteOver30);
    assertSameValue('', $lead->roistatVisit);
});

test('accepts zero price', static function (): void {
    $lead = LeadRequestValidator::validate([
        'phone' => '12345',
        'price' => '0',
    ]);

    assertSameValue(0, $lead->price);
});

test('rejects invalid contact fields', static function (): void {
    assertValidationErrors([
        'name' => str_repeat('я', 101),
        'email' => 'invalid-email',
        'phone' => '12ab',
    ], ['name', 'email', 'phone']);
});

test('rejects invalid deal fields', static function (): void {
    assertValidationErrors([
        'phone' => '12345',
        'price' => '123456789',
        'timeOnSiteOver30' => 'false',
        'roistatVisit' => ['not-a-string'],
    ], ['price', 'timeOnSiteOver30', 'roistatVisit']);
});

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
