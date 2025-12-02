<?php

namespace App\Order\Dto;

class OrderDto
{
    /**
     * @param OrderLineItemDto[] $lineItems
     */
    public function __construct(
        public string $externalId,
        public string $currency,
        public float $totalPrice,
        public float $subtotalPrice,
        public string $financialStatus,
        public string $fulfillmentStatus,
        public \DateTimeInterface $createdAt,
        public OrderCustomerDto $customer,
        public OrderAddressDto $shippingAddress,
        public OrderAddressDto $billingAddress,
        public array $lineItems = []
    ) {}

    public static function fromImport(OrderImportDto $i): self
    {
        return new self(
            externalId: $i->externalId,
            currency: $i->currency,
            totalPrice: $i->totalPrice,
            subtotalPrice: $i->subtotalPrice,
            financialStatus: $i->financialStatus,
            fulfillmentStatus: $i->fulfillmentStatus,
            createdAt: new \DateTimeImmutable($i->createdAt),
            customer: $i->customer,
            shippingAddress: $i->shippingAddress,
            billingAddress: $i->billingAddress,
            lineItems: $i->lineItems
        );
    }
}
