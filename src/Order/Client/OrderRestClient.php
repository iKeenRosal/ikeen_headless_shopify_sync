<?php

namespace App\Order\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OrderRestClient implements OrderClientInterface
{
    private string $baseUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $shopDomain,
        private string $accessToken,
        private string $apiVersion,
    ) {
        $this->baseUrl = "https://{$shopDomain}/admin/api/{$apiVersion}";
    }

    public function upsertOrder(array $payload): array
    {
        // REST: Orders.json endpoint always creates
        $response = $this->httpClient->request(
            'POST',
            "{$this->baseUrl}/orders.json",
            [
                'headers' => $this->headers(),
                'json' => ['order' => $payload]
            ]
        );

        return $response->toArray(false);
    }

    public function findOrderByExternalId(string $externalId): ?array
    {
        $response = $this->httpClient->request(
            'GET',
            "{$this->baseUrl}/orders.json?fields=id,name,order_number,note",
            ['headers' => $this->headers()]
        );

        $orders = $response->toArray(false)['orders'] ?? [];

        foreach ($orders as $order) {
            if (($order['note'] ?? null) === "externalId:{$externalId}") {
                return $order;
            }
        }

        return null;
    }

    public function getOrders(int $minHours, int $maxHours): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $createdAtMin = $now->modify("-{$maxHours} hours")->format(DATE_ATOM);
        $createdAtMax = $now->modify("-{$minHours} hours")->format(DATE_ATOM);

        $url = "{$this->baseUrl}/orders.json?created_at_min={$createdAtMin}&created_at_max={$createdAtMax}&status=any";

        $response = $this->httpClient->request(
            'GET',
            $url,
            ['headers' => $this->headers()]
        );

        return $response->toArray(false)['orders'] ?? [];
    }

    public function getOrderById(string $shopifyId): ?array
    {
        $url = "{$this->baseUrl}/orders/{$shopifyId}.json";

        $response = $this->httpClient->request(
            'GET',
            $url,
            ['headers' => $this->headers()]
        );

        $data = $response->toArray(false);

        // Shopify returns: { "order": { ... } }
        return $data['order'] ?? null;
    }

    public function updateOrder(string $shopifyId, array $payload): array
    {
        $url = "{$this->baseUrl}/orders/{$shopifyId}.json";

        $response = $this->httpClient->request(
            'PUT',
            $url,
            [
                'headers' => $this->headers(),
                'json' => ['order' => $payload]
            ]
        );

        return $response->toArray(false)['order'] ?? [];
    }

    public function cancelOrder(string $shopifyId): array
    {
        $url = "{$this->baseUrl}/orders/{$shopifyId}/cancel.json";

        $response = $this->httpClient->request(
            'POST',
            $url,
            ['headers' => $this->headers()]
        );

        return $response->toArray(false);
    }

    public function createFulfillment(string $orderId, array $fulfillmentPayload): array
    {
        $response = $this->httpClient->request(
            'POST',
            "{$this->baseUrl}/orders/{$orderId}/fulfillments.json",
            [
                'headers' => $this->headers(),
                'json' => ['fulfillment' => $fulfillmentPayload]
            ]
        );
    
        return $response->toArray(false)['fulfillment'] ?? [];
    }

    public function createRefund(string $orderId, array $refundPayload): array
    {
        $url = "{$this->baseUrl}/orders/{$orderId}/refunds.json";
    
        $response = $this->httpClient->request(
            'POST',
            $url,
            [
                'headers' => $this->headers(),
                'json' => ['refund' => $refundPayload]
            ]
        );
    
        return $response->toArray(false)['refund'] ?? [];
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

    public function cancelLineItems(string $orderId, array $lineItemCancellations): array
    {
        $payload = [
            'refund' => [
                'refund_line_items' => $lineItemCancellations,
                'notify' => false
            ]
        ];

        $response = $this->httpClient->request(
            'POST',
            "{$this->baseUrl}/orders/{$orderId}/refunds.json",
            [
                'headers' => $this->headers(),
                'json' => $payload,
            ]
        );

        return $response->toArray(false)['refund'] ?? [];
    }

    public function createPartialFulfillment(string $orderId, array $lineItems): array
    {
        $response = $this->httpClient->request(
            'POST',
            "{$this->baseUrl}/orders/{$orderId}/fulfillments.json",
            [
                'headers' => $this->headers(),
                'json' => [
                    'fulfillment' => [
                        'line_items_by_fulfillment_order' => $lineItems
                    ]
                ]
            ]
        );
    
        return $response->toArray(false)['fulfillment'] ?? [];
    }

    public function updateTracking(string $fulfillmentId, array $trackingData): array
    {
        $response = $this->httpClient->request(
            'POST',
            "{$this->baseUrl}/fulfillments/{$fulfillmentId}/update_tracking.json",
            [
                'headers' => $this->headers(),
                'json' => [
                    'fulfillment' => $trackingData
                ]
            ]
        );

        return $response->toArray(false)['fulfillment'] ?? [];
    }

    private function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $this->accessToken,
        ];
    }
}
