<?php

namespace App\Order\Dto;

class TrackingInfoDto
{
    public function __construct(
        public string $number,
        public ?string $company = null,
        public ?string $url = null
    ) {}
}
