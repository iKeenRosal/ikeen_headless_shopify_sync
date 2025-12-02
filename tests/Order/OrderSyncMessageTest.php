<?php

namespace App\Tests\Order;

use App\Order\Dto\OrderImportDto;
use App\Order\Dto\OrderCustomerDto;
use App\Order\Dto\OrderAddressDto;
use App\Order\Message\OrderSyncMessage;
use PHPUnit\Framework\TestCase;

class OrderSyncMessageTest extends TestCase
{
    public function testMessageHoldsOrderImportDto()
    {
        $customer = new OrderCustomerDto(
            externalId: '101',
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            phone: null
        );

        $address = new OrderAddressDto(
            firstName: 'John',
            lastName: 'Doe',
            company: null,
            address1: '123 Main St',
            address2: null,
            city: 'Cityville',
            province: 'CA',
            country: 'USA',
            postalCode: '90001',
            phone: null,
        );

        $dto = new OrderImportDto(
            externalId: 'XYZ123',
            source: 'shopify',
            currency: 'USD',
            totalPrice: 100.00,
            subtotalPrice: 80.00,
            financialStatus: 'paid',
            fulfillmentStatus: 'fulfilled',
            createdAt: '2025-01-01T12:00:00Z',
            customer: $customer,
            shippingAddress: $address,
            billingAddress: $address,
            lineItems: [],
            raw: []
        );

        $msg = new OrderSyncMessage($dto);

        $this->assertSame($dto, $msg->order);
    }
}
