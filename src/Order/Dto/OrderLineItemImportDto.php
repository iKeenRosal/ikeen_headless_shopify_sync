<?php

namespace App\Order\Dto;

class OrderLineItemImportDto
{
    public function __construct(
        public string $externalId,
        public string $sku,
        public string $title,
        public int $quantity,
        public float $price,
        public ?string $variantId = null,
        public ?string $productId = null,
    ) {}
}
