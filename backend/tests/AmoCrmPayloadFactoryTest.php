<?php

declare(strict_types=1);

use App\AmoCrm\AmoCrmPayloadFactory;
use App\Domain\LeadData;

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

test('builds a complete contact and lead payload', static function (): void {
    $payload = (new AmoCrmPayloadFactory())->create(new LeadData(
        name: 'Анна',
        email: 'anna@example.com',
        phone: '0012345',
        price: 1250,
        timeOnSiteOver30: true,
        roistatVisit: 'visit-42',
    ));

    assertSameValue([
        'name' => 'Заявка с сайта',
        'price' => 1250,
        'custom_fields_values' => [
            [
                'field_id' => 978093,
                'values' => [['value' => 'visit-42']],
            ],
            [
                'field_id' => 978095,
                'values' => [['value' => true]],
            ],
        ],
        '_embedded' => [
            'contacts' => [
                [
                    'custom_fields_values' => [
                        [
                            'field_code' => 'PHONE',
                            'values' => [[
                                'value' => '0012345',
                                'enum_code' => 'WORK',
                            ]],
                        ],
                        [
                            'field_code' => 'EMAIL',
                            'values' => [[
                                'value' => 'anna@example.com',
                                'enum_code' => 'WORK',
                            ]],
                        ],
                    ],
                    'name' => 'Анна',
                ],
            ],
        ],
    ], $payload);
});

test('omits empty optional contact and Roistat values', static function (): void {
    $payload = (new AmoCrmPayloadFactory())->create(new LeadData(
        name: '',
        email: '',
        phone: '12345',
        price: 0,
        timeOnSiteOver30: false,
        roistatVisit: '',
    ));

    assertSameValue([
        [
            'field_id' => 978095,
            'values' => [['value' => false]],
        ],
    ], $payload['custom_fields_values']);
    assertSameValue([
        'custom_fields_values' => [
            [
                'field_code' => 'PHONE',
                'values' => [[
                    'value' => '12345',
                    'enum_code' => 'WORK',
                ]],
            ],
        ],
    ], $payload['_embedded']['contacts'][0]);
});

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
