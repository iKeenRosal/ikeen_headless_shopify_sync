<?php

namespace App\Tests\Order;

use App\Order\Client\OrderRestClient;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class OrderRestClientTest extends TestCase
{
    protected function setUp(): void
    {
        putenv("SHOPIFY_API_DRIVER=rest");
    }

    private function fakeHttpResponse(array $data): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($data);
        return $response;
    }

    private function makeClient(ResponseInterface $response): OrderRestClient
    {
        $mockHttp = $this->createMock(HttpClientInterface::class);
        $mockHttp
            ->method('request')
            ->willReturn($response);

        return new OrderRestClient(
            httpClient: $mockHttp,
            shopDomain: 'test-shop.myshopify.com',
            accessToken: 'token123',
            apiVersion: '2023-10'
        );
    }

    public function testUpsertOrder(): void
    {
        $response = $this->fakeHttpResponse([
            'order' => ['id' => 987]
        ]);

        $client = $this->makeClient($response);

        $result = $client->upsertOrder([
            'email' => 'test@example.com'
        ]);

        $this->assertEquals(987, $result['order']['id']);
    }

    public function testFindOrderByExternalId(): void
    {
        $response = $this->fakeHttpResponse([
            'orders' => [
                [
                    'id' => 888,
                    'note' => 'externalId:ABC123'
                ]
            ]
        ]);

        $client = $this->makeClient($response);

        $result = $client->findOrderByExternalId('ABC123');

        $this->assertEquals(888, $result['id']);
    }

    public function testGetOrdersReturnsOrdersWithinWindow(): void
    {
        // Mock response from Shopify REST API
        $mockResponse = $this->fakeHttpResponse([
            'orders' => [
                ['id' => 1, 'created_at' => '2025-01-01T00:00:00Z'],
                ['id' => 2, 'created_at' => '2025-01-01T01:00:00Z'],
            ]
        ]);

        // Mock HttpClient->request()
        $mockHttp = $this->createMock(HttpClientInterface::class);

        $mockHttp->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->callback(function ($url) {
                    // URL should contain created_at_min and created_at_max
                    return str_contains($url, 'created_at_min=') &&
                           str_contains($url, 'created_at_max=') &&
                           str_contains($url, 'status=any');
                }),
                $this->arrayHasKey('headers')
            )
            ->willReturn($mockResponse);

        // Instantiate the client
        $client = new OrderRestClient(
            httpClient:  $mockHttp,
            shopDomain:  'test.myshopify.com',
            accessToken: 'abc123',
            apiVersion:  '2025-01'
        );

        // Call function we are testing
        $orders = $client->getOrders(minHours: 1, maxHours: 72);

        // Assertions
        $this->assertCount(2, $orders);
        $this->assertEquals(1, $orders[0]['id']);
        $this->assertEquals(2, $orders[1]['id']);
    }

    public function testGetOrderById(): void
    {
        $http = $this->createMock(HttpClientInterface::class);

        $http->method('request')
            ->willReturn($this->fakeHttpResponse([
                'order' => [
                    'id' => 555,
                    'name' => '#555'
                ]
            ]));

        $client = new OrderRestClient(
            httpClient: $http,
            shopDomain: 'test-shop.myshopify.com',
            accessToken: 'abc123',
            apiVersion: '2025-01'
        );

        $result = $client->getOrderById('555');

        $this->assertEquals(555, $result['id']);
        $this->assertEquals('#555', $result['name']);
    }

    public function testUpdateOrder(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')
            ->willReturn(['order' => ['id' => 555, 'note' => 'Updated note']]);

        $http = $this->createMock(HttpClientInterface::class);

        $http->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                $this->stringContains('/orders/555.json'),
                $this->callback(function($options) {
                    return isset($options['json']['order']);
                })
            )
            ->willReturn($mockResponse);

        $client = new OrderRestClient(
            httpClient: $http,
            shopDomain: 'test-shop.myshopify.com',
            accessToken: 'token123',
            apiVersion: '2025-01'
        );

        $result = $client->updateOrder('555', ['note' => 'Updated note']);

        $this->assertEquals(555, $result['id']);
        $this->assertEquals('Updated note', $result['note']);
    }

    public function testCancelOrder(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'order' => [
                'id' => 12345,
                'cancel_reason' => 'customer',
                'cancelled_at' => '2025-01-01T12:00:00Z'
            ]
        ]);

        $mockHttp = $this->createMock(HttpClientInterface::class);
        $mockHttp->method('request')->willReturn($response);

        $client = new OrderRestClient(
            httpClient: $mockHttp,
            shopDomain: 'test.myshopify.com',
            accessToken: 'abc',
            apiVersion: '2025-01'
        );

        $result = $client->cancelOrder('12345');

        $this->assertEquals(12345, $result['order']['id']);
        $this->assertEquals('customer', $result['order']['cancel_reason']);
    }

    public function testCreateFulfillment(): void
    {
        $mockResponse = $this->fakeHttpResponse([
            'fulfillment' => [
                'id' => 555,
                'status' => 'success'
            ]
        ]);
    
        $client = $this->makeClient($mockResponse);
    
        $result = $client->createFulfillment(
            orderId: "12345",
            fulfillmentPayload: [
                'tracking_number' => '1Z9999',
                'location_id' => 123,
            ]
        );
    
        $this->assertEquals(555, $result['id']);
        $this->assertEquals('success', $result['status']);
    }

    public function testCreateRefund(): void
    {
        $http = $this->createMock(HttpClientInterface::class);

        $http->method('request')->willReturn(
            $this->fakeHttpResponse([
                'refund' => [
                    'id' => 444,
                    'created_at' => '2025-01-01T12:00:00Z'
                ]
            ])
        );

        $client = new OrderRestClient(
            httpClient: $http,
            shopDomain: 'test.myshopify.com',
            accessToken: 'xyz',
            apiVersion: '2025-01'
        );

        $result = $client->createRefund(
            orderId: '123456',
            refundPayload: ['note' => 'Customer returned item']
        );

        $this->assertEquals(444, $result['id']);
    }

    public function testCancelLineItems(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'refund' => [
                'id' => 555,
                'created_at' => '2025-01-01T00:00:00Z'
            ]
        ]);
    
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('request')->willReturn($mockResponse);
    
        $client = new OrderRestClient(
            httpClient: $http,
            shopDomain: 'test.myshopify.com',
            accessToken: 'abc',
            apiVersion: '2025-01'
        );
    
        $result = $client->cancelLineItems('12345', [
            [
                'line_item_id' => 999,
                'quantity' => 1
            ]
        ]);
    
        $this->assertEquals(555, $result['id']);
    }

    public function testCreatePartialFulfillment(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'fulfillment' => [
                'id' => 555,
                'status' => 'success'
            ]
        ]);

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('request')->willReturn($mockResponse);

        $client = new OrderRestClient(
            httpClient: $http,
            shopDomain: 'test.myshopify.com',
            accessToken: 'abc',
            apiVersion: '2025-01'
        );

        $lineItems = [
            [
                'fulfillment_order_id' => 10,
                'fulfillment_order_line_items' => [
                    ['id' => 200, 'quantity' => 1]
                ]
            ]
        ];

        $result = $client->createPartialFulfillment('12345', $lineItems);

        $this->assertEquals(555, $result['id']);
        $this->assertEquals('success', $result['status']);
    }

    public function testUpdateTracking()
    {
        $response = $this->fakeHttpResponse([
            'fulfillment' => [
                'id' => 777,
                'tracking_number' => 'TRACK123'
            ]
        ]);
    
        $client = $this->makeClient($response);
    
        $result = $client->updateTracking('777', [
            'tracking_number' => 'TRACK123',
            'tracking_company' => 'UPS'
        ]);
    
        $this->assertEquals('TRACK123', $result['tracking_number']);
    }
    
}
