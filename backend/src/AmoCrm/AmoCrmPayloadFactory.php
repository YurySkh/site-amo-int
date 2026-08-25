<?php

declare(strict_types=1);

namespace App\AmoCrm;

use App\Domain\LeadData;

final class AmoCrmPayloadFactory
{
    private const int ROISTAT_FIELD_ID = 978093;
    private const int TIME_ON_SITE_FIELD_ID = 978095;

    /**
     * @return array<string, mixed>
     */
    public function create(LeadData $lead): array
    {
        $contact = [
            'custom_fields_values' => $this->contactCustomFields($lead),
        ];

        if ($lead->name !== '') {
            $contact['name'] = $lead->name;
        }

        return [
            'name' => 'Заявка с сайта',
            'price' => $lead->price,
            'custom_fields_values' => $this->leadCustomFields($lead),
            '_embedded' => [
                'contacts' => [$contact],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function contactCustomFields(LeadData $lead): array
    {
        $fields = [
            [
                'field_code' => 'PHONE',
                'values' => [
                    [
                        'value' => $lead->phone,
                        'enum_code' => 'WORK',
                    ],
                ],
            ],
        ];

        if ($lead->email !== '') {
            $fields[] = [
                'field_code' => 'EMAIL',
                'values' => [
                    [
                        'value' => $lead->email,
                        'enum_code' => 'WORK',
                    ],
                ],
            ];
        }

        return $fields;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function leadCustomFields(LeadData $lead): array
    {
        $fields = [];

        if ($lead->roistatVisit !== '') {
            $fields[] = [
                'field_id' => self::ROISTAT_FIELD_ID,
                'values' => [
                    ['value' => $lead->roistatVisit],
                ],
            ];
        }

        $fields[] = [
            'field_id' => self::TIME_ON_SITE_FIELD_ID,
            'values' => [
                ['value' => $lead->timeOnSiteOver30],
            ],
        ];

        return $fields;
    }
}
