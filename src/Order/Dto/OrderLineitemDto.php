<?php

namespace App\Order\Dto;

class OrderLineItemDto
{
    public function __construct(
        public string $externalId,
        public string $title,
        public int $quantity,
        public float $price,
        public string $currency = 'USD',
        public ?string $sku = null,
        public ?string $variantId = null,
    ) {}
}
