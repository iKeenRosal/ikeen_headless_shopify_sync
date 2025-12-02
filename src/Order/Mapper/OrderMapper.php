<?php

namespace App\Order\Mapper;

use App\Order\Dto\OrderImportDto;
use App\Order\Dto\OrderDto;
use App\Order\Dto\OrderLineItemDto;
use App\Order\Dto\OrderCustomerDto;
use App\Order\Dto\OrderAddressDto;

class OrderMapper
{
    /**
     * Map external payload (API webhook, cron fetch, etc.)
     * into a standardized OrderImportDto.
     */
    public function map(array $payload): OrderImportDto
    {
        if (empty($payload['externalId'])) {
            throw new \Exception("Order payload missing required field: externalId");
        }

        if (empty($payload['lineItems']) || !is_array($payload['lineItems'])) {
            throw new \Exception("Order payload must contain at least one line item.");
        }

        // Map customer
        $customer = new OrderCustomerDto(
            externalId: $payload['customer']['externalId'] ?? null,
            firstName: $payload['customer']['firstName'] ?? null,
            lastName: $payload['customer']['lastName'] ?? null,
            email: $payload['customer']['email'] ?? null,
            phone: $payload['customer']['phone'] ?? null,
        );

        // Map addresses
        $shippingAddress = isset($payload['shippingAddress'])
            ? $this->mapAddress($payload['shippingAddress'])
            : null;

        $billingAddress = isset($payload['billingAddress'])
            ? $this->mapAddress($payload['billingAddress'])
            : null;

        // Map line items
        $lineItems = array_map(
            fn($item) => new OrderLineItemDto(
                externalId: $item['externalId'] ?? null,
                sku: $item['sku'] ?? null,
                quantity: $item['quantity'] ?? 1,
                price: (float)($item['price'] ?? 0),
                title: $item['title'] ?? null,
                variantTitle: $item['variantTitle'] ?? null,
            ),
            $payload['lineItems']
        );

        return new OrderImportDto(
            externalId:        $payload['externalId'],
            financialStatus:   $payload['financialStatus'] ?? null,
            fulfillmentStatus: $payload['fulfillmentStatus'] ?? null,
            currency:          $payload['currency'] ?? 'USD',
            subtotal:          (float)($payload['subtotal'] ?? 0),
            total:             (float)($payload['total'] ?? 0),
            createdAt:         $payload['createdAt'] ?? null,
            updatedAt:         $payload['updatedAt'] ?? null,
            customer:          $customer,
            shippingAddress:   $shippingAddress,
            billingAddress:    $billingAddress,
            lineItems:         $lineItems,
        );
    }

    /**
     * After validation, convert validated import DTO → internal OrderDto
     */
    public function toDomain(OrderImportDto $import): OrderDto
    {
        return new OrderDto(
            externalId:        $import->externalId,
            financialStatus:   $import->financialStatus,
            fulfillmentStatus: $import->fulfillmentStatus,
            currency:          $import->currency,
            subtotal:          $import->subtotal,
            total:             $import->total,
            createdAt:         $import->createdAt,
            updatedAt:         $import->updatedAt,
            customer:          $import->customer,
            shippingAddress:   $import->shippingAddress,
            billingAddress:    $import->billingAddress,
            lineItems:         $import->lineItems,
        );
    }

    /**
     * Helper: map address arrays to DTO
     */
    private function mapAddress(array $a): OrderAddressDto
    {
        return new OrderAddressDto(
            name:        $a['name']        ?? null,
            phone:       $a['phone']       ?? null,
            address1:    $a['address1']    ?? null,
            address2:    $a['address2']    ?? null,
            city:        $a['city']        ?? null,
            province:    $a['province']    ?? null,
            postalCode:  $a['postalCode']  ?? null,
            country:     $a['country']     ?? null
        );
    }
}
