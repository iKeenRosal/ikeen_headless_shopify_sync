<?php

namespace App\Order\Dto;

class OrderImportDto
{
    /**
     * @param OrderLineItemDto[] $lineItems
     */
    public function __construct(
        public string $externalId,
        public string $source, // "shopify", "tiktok", "meta", etc.
        public string $currency,
        public float $totalPrice,
        public float $subtotalPrice,
        public string $financialStatus,
        public string $fulfillmentStatus,
        public string $createdAt,
        public OrderCustomerDto $customer,
        public OrderAddressDto $shippingAddress,
        public OrderAddressDto $billingAddress,
        public array $lineItems = [],
        public array $raw = [], // store original raw payload
    ) {}
}
