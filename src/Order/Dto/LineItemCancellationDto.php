<?php

namespace App\Order\Dto;

class LineItemCancellationDto
{
    public function __construct(
        public string $lineitemId,       // Shopify line item ID
        public string $sku,              // Product Sku
        public int $quantity,            // quantity to cancel
        public ?string $reason = null,   // "customer" | "inventory" | etc.
    ) {}
}
