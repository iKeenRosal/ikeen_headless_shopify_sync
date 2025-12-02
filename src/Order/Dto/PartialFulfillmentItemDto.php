<?php

namespace App\Order\Dto;

class PartialFulfillmentItemDto
{
    public function __construct(
        public string $lineItemId,  // Shopify line item ID
        public int $quantity        // quantity fulfilled
    ) {}
}
