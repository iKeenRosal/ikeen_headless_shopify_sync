<?php

namespace App\Order\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OrderGraphqlClient implements OrderClientInterface
{
    private string $baseUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $shopDomain,
        private string $accessToken,
        private string $apiVersion
    ) {
        $this->baseUrl = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}";
    }

    public function findOrderByExternalId(string $externalId): ?array
    {
        $query = <<<GQL
        query FindOrder(\$externalId: String!) {
            orders(first: 1, query: \$externalId) {
                nodes {
                    id
                    name
                }
            }
        }
        GQL;

        $result = $this->query($query, ['externalId' => $externalId]);

        return $result['orders']['nodes'][0] ?? null;
    }

    public function upsertOrder(array $input): array
    {
        // Simplified for now
        $mutation = <<<GQL
        mutation UpsertOrder(\$input: DraftOrderInput!) {
            draftOrderCreate(input: \$input) {
                draftOrder {
                    id
                    name
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $response = $this->query($mutation, ['input' => $input]);

        if (!empty($response['draftOrderCreate']['userErrors'])) {
            throw new \Exception(
                "Order GraphQL Error: " . json_encode($response['draftOrderCreate']['userErrors'])
            );
        }

        return $response['draftOrderCreate']['draftOrder'];
    }

    /**
     * Base GraphQL requester
     */
    public function query(string $query, array $variables = []): array
    {
        $response = $this->httpClient->request(
            'POST',
            "{$this->baseUrl}/graphql.json",
            [
                'headers' => $this->headers(),
                'json' => [
                    'query' => $query,
                    'variables' => $variables
                ]
            ]
        );

        $data = $response->toArray(false);

        if (isset($data['errors'])) {
            throw new \Exception("Shopify GraphQL Error: " . json_encode($data['errors']));
        }

        return $data['data'] ?? [];
    }

    public function getOrders(int $minHours, int $maxHours): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        // Shopify GraphQL requires ISO8601 timestamps
        $createdAtMin = $now->modify("-{$maxHours} hours")->format(DATE_ATOM);
        $createdAtMax = $now->modify("-{$minHours} hours")->format(DATE_ATOM);

        // Build Shopify GraphQL search query string
        // Format: created_at:>=2025-01-01T00:00:00Z created_at:<=2025-01-03T00:00:00Z
        $searchQuery = "created_at:>={$createdAtMin} created_at:<={$createdAtMax}";

        $query = <<<GQL
        query FindOrders(\$query: String!) {
            orders(first: 50, query: \$query) {
                nodes {
                    id
                    name
                    createdAt
                    totalPriceSet {
                        shopMoney {
                            amount
                            currencyCode
                        }
                    }
                }
            }
        }
        GQL;

        $result = $this->query($query, ['query' => $searchQuery]);

        return $result['orders']['nodes'] ?? [];
    }

    public function getOrderById(string $shopifyId): ?array
    {
        $query = <<<GQL
        query GetOrderById(\$id: ID!) {
            order(id: \$id) {
                id
                name
                createdAt
                currencyCode
                totalPriceSet {
                    shopMoney {
                        amount
                        currencyCode
                    }
                }
                customer {
                    firstName
                    lastName
                    email
                }
                lineItems(first: 50) {
                    edges {
                        node {
                            title
                            quantity
                            originalUnitPriceSet {
                                shopMoney {
                                    amount
                                    currencyCode
                                }
                            }
                        }
                    }
                }
            }
        }
        GQL;

        $result = $this->query($query, ['id' => $shopifyId]);

        return $result['order'] ?? null;
    }

    public function updateOrder(string $shopifyId, array $payload): array
    {
        $mutation = <<<GQL
        mutation UpdateDraftOrder(\$id: ID!, \$input: DraftOrderInput!) {
            draftOrderUpdate(id: \$id, input: \$input) {
                draftOrder {
                    id
                    name
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $variables = [
            'id' => $shopifyId,
            'input' => $payload
        ];

        $result = $this->query($mutation, $variables);

        $update = $result['draftOrderUpdate'] ?? [];

        if (!empty($update['userErrors'])) {
            throw new \Exception(
                "GraphQL Order Update Error: " . json_encode($update['userErrors'])
            );
        }

        return $update['draftOrder'] ?? [];
    }

    public function testUpdateOrderSendsMutation(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')
            ->willReturn([
                'data' => [
                    'draftOrderUpdate' => [
                        'draftOrder' => [
                            'id' => 'gid://shopify/DraftOrder/777',
                            'name' => 'DRAFT-777'
                        ],
                        'userErrors' => []
                    ]
                ]
            ]);

        $http = $this->createMock(HttpClientInterface::class);

        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->stringContains('/graphql.json'),
                $this->callback(function ($payload) {
                    return isset($payload['json']['query'])
                        && isset($payload['json']['variables']['id'])
                        && $payload['json']['variables']['id'] === 'gid://shopify/DraftOrder/777';
                })
            )
            ->willReturn($mockResponse);

        $client = new OrderGraphqlClient(
            httpClient: $http,
            shopDomain: 'test-shop.myshopify.com',
            accessToken: 'token123',
            apiVersion: '2025-01'
        );

        $result = $client->updateOrder(
            'gid://shopify/DraftOrder/777',
            ['note' => 'Updated via GraphQL']
        );

        $this->assertEquals('gid://shopify/DraftOrder/777', $result['id']);
        $this->assertEquals('DRAFT-777', $result['name']);
    }

    public function cancelOrder(string $shopifyId): array
    {
        $mutation = <<<GQL
        mutation CancelOrder(\$id: ID!) {
            orderCancel(id: \$id) {
                order {
                    id
                    name
                    cancelReason
                    cancelledAt
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $result = $this->query($mutation, ['id' => $shopifyId]);

        $cancelData = $result['orderCancel'] ?? null;

        if (!$cancelData) {
            throw new \Exception("Invalid GraphQL response: missing orderCancel");
        }

        if (!empty($cancelData['userErrors'])) {
            throw new \Exception("OrderCancel Error: " . json_encode($cancelData['userErrors']));
        }

        return $cancelData['order'];
    }

    public function createFulfillment(string $orderId, array $fulfillmentPayload): array
    {
        $mutation = <<<GQL
        mutation FulfillOrder(\$orderId: ID!, \$input: FulfillmentInput!) {
            fulfillmentCreate(orderId: \$orderId, input: \$input) {
                fulfillment {
                    id
                    status
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;
    
        $result = $this->query($mutation, [
            'orderId' => $orderId,
            'input'   => $fulfillmentPayload
        ]);
    
        $create = $result['fulfillmentCreate'] ?? null;
    
        if (!$create) {
            throw new \Exception("Invalid GraphQL response");
        }
    
        if (!empty($create['userErrors'])) {
            throw new \Exception(
                "Fulfillment Error: " . json_encode($create['userErrors'])
            );
        }
    
        return $create['fulfillment'] ?? [];
    }

    public function createRefund(string $orderId, array $refundPayload): array
    {
        $mutation = <<<GQL
        mutation refundCreate(\$orderId: ID!, \$input: RefundInput!) {
            refundCreate(orderId: \$orderId, refund: \$input) {
                refund {
                    id
                    legacyResourceId
                    createdAt
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $response = $this->query($mutation, [
            'orderId' => $orderId,
            'input'   => $refundPayload
        ]);

        $result = $response['refundCreate'];

        if (!empty($result['userErrors'])) {
            throw new \Exception("Refund GraphQL Error: " . json_encode($result['userErrors']));
        }

        return $result['refund'];
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
    
    public function cancelLineItems(string $orderId, array $lineItemCancellations): array
    {
        $mutation = <<<GQL
        mutation RefundLineItems(\$orderId: ID!, \$refund: RefundInput!) {
            refundCreate(orderId: \$orderId, refund: \$refund) {
                refund {
                    id
                    createdAt
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        // Build refund input
        $refundInput = [
            'refundLineItems' => $lineItemCancellations,
            'notify' => false,
        ];

        $result = $this->query($mutation, [
            'orderId' => $orderId,
            'refund'  => $refundInput,
        ]);

        if (!empty($result['refundCreate']['userErrors'])) {
            throw new \Exception(
                "Refund GraphQL Error: " . json_encode($result['refundCreate']['userErrors'])
            );
        }

        return $result['refundCreate']['refund'];
    }

    public function createPartialFulfillment(string $orderId, array $lineItems): array
    {
        $mutation = <<<GQL
            mutation CreatePartialFulfillment(\$input: FulfillmentCreateV2Input!) {
                fulfillmentCreateV2(input: \$input) {
                    fulfillment {
                        id
                        status
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GQL;

        $variables = [
            'input' => [
                'lineItemsByFulfillmentOrder' => $lineItems
            ]
        ];

        $result = $this->query($mutation, $variables);

        $node = $result['fulfillmentCreateV2'];

        if (!empty($node['userErrors'])) {
            throw new \Exception(
                "GraphQL Fulfillment Error: " . json_encode($node['userErrors'])
            );
        }

        return $node['fulfillment'] ?? [];
    }

    public function testCreatePartialFulfillment(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'data' => [
                'fulfillmentCreateV2' => [
                    'fulfillment' => [
                        'id' => 'gid://shopify/Fulfillment/999',
                        'status' => 'SUCCESS'
                    ],
                    'userErrors' => []
                ]
            ]
        ]);
    
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('request')->willReturn($mockResponse);
    
        $client = new OrderGraphqlClient(
            httpClient: $http,
            shopDomain: 'test.myshopify.com',
            accessToken: 'abc',
            apiVersion: '2025-01'
        );
    
        $lineItems = [
            [
                'fulfillmentOrderId' => 'gid://shopify/FulfillmentOrder/10',
                'fulfillmentOrderLineItems' => [
                    ['id' => 'gid://shopify/FulfillmentOrderLineItem/200', 'quantity' => 1]
                ]
            ]
        ];
    
        $result = $client->createPartialFulfillment('12345', $lineItems);
    
        $this->assertEquals('gid://shopify/Fulfillment/999', $result['id']);
        $this->assertEquals('SUCCESS', $result['status']);
    }
    
    public function updateTracking(string $fulfillmentId, array $trackingInfo): array
    {
        $mutation = <<<GQL
        mutation fulfillmentTrackingUpdate(
            \$fulfillmentId: ID!,
            \$trackingInfo: FulfillmentTrackingInput!
        ) {
            fulfillmentTrackingUpdate(
                fulfillmentId: \$fulfillmentId,
                trackingInfo: \$trackingInfo
            ) {
                fulfillment {
                    id
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $result = $this->query($mutation, [
            'fulfillmentId' => $fulfillmentId,
            'trackingInfo'  => $trackingInfo
        ]);

        if (!empty($result['fulfillmentTrackingUpdate']['userErrors'])) {
            throw new \Exception(
                "Shopify Tracking Update Error: "
                . json_encode($result['fulfillmentTrackingUpdate']['userErrors'])
            );
        }

        return $result['fulfillmentTrackingUpdate']['fulfillment'] ?? null;
    }

    private function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $this->accessToken,
        ];
    }
}
