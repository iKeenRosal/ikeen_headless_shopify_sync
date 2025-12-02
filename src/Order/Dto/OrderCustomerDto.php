<?php

namespace App\Order\Dto;

class OrderCustomerDto
{
    public function __construct(
        public string $externalId,
        public string $firstName,
        public string $lastName,
        public ?string $email = null,
        public ?string $phone = null,
    ) {}
}
