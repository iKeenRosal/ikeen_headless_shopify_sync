<?php

namespace App\Tests\Controller;

use App\Order\Controller\OrderSyncController;
use App\Order\Dto\OrderImportDto;
use App\Order\Dto\OrderCustomerDto;
use App\Order\Dto\OrderAddressDto;
use App\Order\Dto\OrderLineItemDto;
use App\Order\Mapper\OrderMapper;
use App\Order\Message\OrderSyncMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;

class OrderSyncControllerTest extends TestCase
{
    public function testSyncSingleOrderQueuesMessage()
    {
        $mapper = $this->createMock(OrderMapper::class);
        $bus    = $this->createMock(MessageBusInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $payload = [
            'externalId' => 'ORDER123',
            'source' => 'shopify',
            'currency' => 'USD',
            'totalPrice' => 59.99,
            'subtotalPrice' => 49.99,
            'financialStatus' => 'paid',
            'fulfillmentStatus' => 'unfulfilled',
            'createdAt' => '2025-01-01T10:00:00Z',
            'customer' => [],
            'shippingAddress' => [],
            'billingAddress' => [],
            'lineItems' => [],
        ];

        // Build a full DTO that matches your constructor
        $orderDto = new OrderImportDto(
            externalId: 'ORDER123',
            source: 'shopify',
            currency: 'USD',
            totalPrice: 59.99,
            subtotalPrice: 49.99,
            financialStatus: 'paid',
            fulfillmentStatus: 'unfulfilled',
            createdAt: '2025-01-01T10:00:00Z',
            customer: new OrderCustomerDto(
                externalId: 'CUST123',
                firstName: 'John',
                lastName: 'Doe',
                email: 'john@example.com'
            ),
            shippingAddress: new OrderAddressDto(
                firstName: 'John',
                lastName: 'Doe',
                address1: '123 Main St',
                city: 'LA',
                country: 'US'
            ),
            billingAddress: new OrderAddressDto(
                firstName: 'John',
                lastName: 'Doe',
                address1: '123 Main St',
                city: 'LA',
                country: 'US'
            ),
            lineItems: [
                new OrderLineItemDto(
                    externalId: 'LINE1',
                    title: 'Test Product',
                    quantity: 1,
                    price: 59.99,
                    sku: 'SKU123'
                )
            ],
            raw: $payload
        );

        // Mapper returns the DTO
        $mapper->method('map')->willReturn($orderDto);

        // Expect ONE dispatch
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($msg) use ($orderDto) {
                return $msg instanceof OrderSyncMessage
                       && $msg->order === $orderDto;
            }))
            ->willReturnCallback(fn($m) => new Envelope($m));

        $controller = new OrderSyncController($mapper, $bus, $logger);

        $request = new Request(content: json_encode($payload));

        $response = $controller->sync($request);

        $this->assertEquals(202, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals('queued', $data['status']);
        $this->assertEquals(['ORDER123'], $data['externalIds']);
        $this->assertEquals(1, $data['count']);
    }

    public function testMessageHoldsOrderImportDto()
    {
        $dto = new OrderImportDto(
            externalId: 'ORDER999',
            source: 'shopify',
            currency: 'USD',
            totalPrice: 100.00,
            subtotalPrice: 90.00,
            financialStatus: 'paid',
            fulfillmentStatus: 'unfulfilled',
            createdAt: '2025-02-01T10:00:00Z',
            customer: new OrderCustomerDto(
                externalId: 'CUST999',
                firstName: 'Jane',
                lastName: 'Doe',
                email: 'jane@example.com'
            ),
            shippingAddress: new OrderAddressDto(
                firstName: 'Jane',
                lastName: 'Doe',
                address1: '123 Main St',
                city: 'LA',
                country: 'US'
            ),
            billingAddress: new OrderAddressDto(
                firstName: 'Jane',
                lastName: 'Doe',
                address1: '123 Main St',
                city: 'LA',
                country: 'US'
            ),
            lineItems: [],
            raw: []
        );

        $msg = new OrderSyncMessage($dto);

        $this->assertSame($dto, $msg->order);
    }
}
