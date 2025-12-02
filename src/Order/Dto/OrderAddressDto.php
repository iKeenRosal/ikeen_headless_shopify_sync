<?php

namespace App\Order\Dto;

class OrderAddressDto
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $company = null,
        public ?string $address1 = null,
        public ?string $address2 = null,
        public ?string $city = null,
        public ?string $province = null,
        public ?string $country = null,
        public ?string $postalCode = null,
        public ?string $phone = null,
    ) {}
}
