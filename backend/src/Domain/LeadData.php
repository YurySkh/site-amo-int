<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class LeadData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public int $price,
        public bool $timeOnSiteOver30,
        public string $roistatVisit,
    ) {
    }

    /**
     * @return array{
     *     name: string,
     *     email: string,
     *     phone: string,
     *     price: int,
     *     timeOnSiteOver30: bool,
     *     roistatVisit: string
     * }
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'price' => $this->price,
            'timeOnSiteOver30' => $this->timeOnSiteOver30,
            'roistatVisit' => $this->roistatVisit,
        ];
    }
}
