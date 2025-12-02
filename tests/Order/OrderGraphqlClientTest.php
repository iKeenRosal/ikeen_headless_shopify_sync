<?php

namespace App\Tests\Order;

use App\Order\Client\OrderGraphqlClient;
use App\Order\Client\OrderClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class OrderGraphqlClientTest extends TestCase
{
    private function fakeGraphqlResponse(array $data): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'data' => $data
        ]);
        return $response;
    }

    private function makeClient(ResponseInterface $response): OrderGraphqlClient
    {
        $mockHttp = $this->createMock(HttpClientInterface::class);

        // Any POST will return our mocked GraphQL response
        $mockHttp
            ->method('request')
            ->willReturn($response);

        return new OrderGraphqlClient(
            httpClient: $mockHttp,
            shopDomain: 'test-shop.myshopify.com',
            accessToken: 'token123',
            apiVersion: '2025-01'
        );
    }

    public function testFindOrderByExternalId(): void
    {
        $response = $this->fakeGraphqlResponse([
            'orders' => [
                'nodes' => [
                    ['id' => 'gid://shopify/Order/1001', 'name' => '#1001']
                ]
            ]
        ]);

        $client = $this->makeClient($response);

        $result = $client->findOrderByExternalId('ABC123');

        $this->assertEquals('gid://shopify/Order/1001', $result['id']);
        $this->assertEquals('#1001', $result['name']);
    }

    public function testUpsertOrderTriggersMutation(): void
    {
        $response = $this->fakeGraphqlResponse([
            'draftOrderCreate' => [
                'draftOrder' => [
                    'id' => 'gid://shopify/DraftOrder/44',
                    'name' => 'DO-44'
                ],
                'userErrors' => []
            ]
        ]);

        $client = $this->makeClient($response);

        $result = $client->upsertOrder(['note' => 'Test draft order']);

        $this->assertEquals('gid://shopify/DraftOrder/44', $result['id']);
        $this->assertEquals('DO-44', $result['name']);
    }

    public function testGetOrdersReturnsNodesWithinWindow(): void
    {
        // Fake GraphQL response
        $response = $this->fakeGraphqlResponse([
            'orders' => [
                'nodes' => [
                    [
                        'id' => 'gid://shopify/Order/111',
                        'name' => '#111',
                        'createdAt' => '2025-01-01T10:00:00Z',
                        'totalPriceSet' => [
                            'shopMoney' => [
                                'amount' => '59.99',
                                'currencyCode' => 'USD'
                            ]
                        ]
                    ],
                    [
                        'id' => 'gid://shopify/Order/222',
                        'name' => '#222',
                        'createdAt' => '2025-01-01T12:00:00Z',
                        'totalPriceSet' => [
                            'shopMoney' => [
                                'amount' => '129.00',
                                'currencyCode' => 'USD'
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        // Build client
        $client = $this->makeClient($response);

        // Call method for test, e.g. orders between 1–72 hours old
        $orders = $client->getOrders(minHours: 1, maxHours: 72);

        // Assertions
        $this->assertCount(2, $orders);
        $this->assertEquals('gid://shopify/Order/111', $orders[0]['id']);
        $this->assertEquals('gid://shopify/Order/222', $orders[1]['id']);
    }

    public function testGetOrderByIdReturnsOrder(): void
    {
        $response = $this->fakeGraphqlResponse([
            'order' => [
                'id' => 'gid://shopify/Order/999',
                'name' => '#999',
                'createdAt' => '2025-01-01T12:00:00Z',
                'currencyCode' => 'USD',
                'totalPriceSet' => [
                    'shopMoney' => ['amount' => '59.99', 'currencyCode' => 'USD']
                ],
                'customer' => [
                    'firstName' => 'Keen',
                    'lastName' => 'Rosal',
                    'email' => 'keen@example.com'
                ],
                'lineItems' => [
                    'edges' => [
                        [
                            'node' => [
                                'title' => 'Red Hoodie',
                                'quantity' => 2,
                                'originalUnitPriceSet' => [
                                    'shopMoney' => ['amount' => '29.99', 'currencyCode' => 'USD']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $client = $this->makeClient($response);

        $result = $client->getOrderById('gid://shopify/Order/999');

        $this->assertNotNull($result);
        $this->assertEquals('gid://shopify/Order/999', $result['id']);
        $this->assertEquals('#999', $result['name']);
    }

    public function testCancelOrder(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $response->method('toArray')->willReturn([
            'data' => [
                'orderCancel' => [
                    'order' => [
                        'id' => 'gid://shopify/Order/555',
                        'name' => '#555',
                        'cancelReason' => 'customer',
                        'cancelledAt' => '2025-01-01T10:00:00Z'
                    ],
                    'userErrors' => []
                ]
            ]
        ]);

        $mockHttp = $this->createMock(HttpClientInterface::class);
        $mockHttp->method('request')->willReturn($response);

        $client = new OrderGraphqlClient(
            httpClient: $mockHttp,
            shopDomain: 'test.myshopify.com',
            accessToken: 'abc',
            apiVersion: '2025-01'
        );

        $result = $client->cancelOrder('gid://shopify/Order/555');

        $this->assertEquals('gid://shopify/Order/555', $result['id']);
        $this->assertEquals('customer', $result['cancelReason']);
    }

    public function testCreateFulfillment(): void
    {
        $mockResponse = $this->fakeGraphqlResponse([
            'fulfillmentCreate' => [
                'fulfillment' => [
                    'id' => 'gid://shopify/Fulfillment/777',
                    'status' => 'SUCCESS'
                ],
                'userErrors' => []
            ]
        ]);

        $client = $this->makeClient($mockResponse);

        $result = $client->createFulfillment(
            orderId: "gid://shopify/Order/123",
            fulfillmentPayload: [
                'trackingInfo' => [
                    [
                        'company' => 'UPS',
                        'number'  => '1ZXXXX'
                    ]
                ]
            ]
        );

        $this->assertEquals('gid://shopify/Fulfillment/777', $result['id']);
        $this->assertEquals('SUCCESS', $result['status']);
    }

    public function testCreateRefundGraphql(): void
    {
        $response = $this->fakeGraphqlResponse([
            'refundCreate' => [
                'refund' => [
                    'id' => 'gid://shopify/Refund/777',
                    'legacyResourceId' => '777',
                    'createdAt' => '2025-01-02T10:00:00Z'
                ],
                'userErrors' => []
            ]
        ]);
    
        $client = $this->makeClient($response);
    
        $result = $client->createRefund(
            orderId: 'gid://shopify/Order/1234',
            refundPayload: ['note' => 'Damaged goods']
        );
    
        $this->assertEquals('gid://shopify/Refund/777', $result['id']);
    }

    public function testCancelLineItems(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'data' => [
                'refundCreate' => [
                    'refund' => [
                        'id' => 'gid://shopify/Refund/777',
                        'createdAt' => '2025-01-01T00:00:00Z'
                    ],
                    'userErrors' => []
                ]
            ]
        ]);

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('request')->willReturn($mockResponse);

        $client = new OrderGraphqlClient(
            httpClient: $http,
            shopDomain: 'test-shop.myshopify.com',
            accessToken: 'token123',
            apiVersion: '2025-01'
        );

        $result = $client->cancelLineItems(
            'gid://shopify/Order/123',
            [
                [
                    'lineItemId' => 'gid://shopify/LineItem/556',
                    'quantity' => 1
                ]
            ]
        );

        $this->assertEquals('gid://shopify/Refund/777', $result['id']);
    }

    public function testUpdateTracking()
    {
        $response = $this->fakeGraphqlResponse([
            'fulfillmentTrackingUpdate' => [
                'fulfillment' => [
                    'id' => 'gid://shopify/Fulfillment/777',
                    'trackingInfo' => [
                        'number' => 'TRACK789',
                        'company' => 'FedEx',
                        'url' => 'https://fedex.com/track/TRACK789'
                    ]
                ],
                'userErrors' => []
            ]
        ]);

        $client = $this->makeClient($response);

        $result = $client->updateTracking(
            'gid://shopify/Fulfillment/777',
            [
                'number' => 'TRACK789',
                'company' => 'FedEx'
            ]
        );

        $this->assertEquals('gid://shopify/Fulfillment/777', $result['id']);
        $this->assertEquals('TRACK789', $result['trackingInfo']['number']);
    }

}
