<?php

namespace App\Product\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProductRestClient implements ProductClientInterface
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

    public function upsertProduct(array $payload): array
    {
        // Your REST logic...
    }

    public function createProduct(array $payload): array
    {
        $response = $this->httpClient->request(
            'POST',
            "{$this->baseUrl}/products.json",
            [
                'headers' => $this->headers(),
                'json' => ['product' => $payload],
            ]
        );

        return $response->toArray(false);
    }

    public function updateProduct(string $shopifyProductId, array $payload): array
    {
        $response = $this->httpClient->request(
            'PUT',
            "{$this->baseUrl}/products/{$shopifyProductId}.json",
            [
                'headers' => $this->headers(),
                'json' => ['product' => $payload],
            ]
        );

        return $response->toArray(false);
    }

    public function getProductByExternalId(string $externalId): ?array
    {
        $response = $this->httpClient->request(
            'GET',
            "{$this->baseUrl}/products.json?fields=id,handle,body_html,variants,title&limit=1&presentment_currencies=USD&status=active&title={$externalId}",
            ['headers' => $this->headers()]
        );

        $data = $response->toArray(false);

        return $data['products'][0] ?? null;
    }

    public function createVariant(int $productId, array $payload): array
    {
        $response = $this->httpClient->request(
            'POST',
            "{$this->baseUrl}/variants.json",
            [
                'headers' => $this->headers(),
                'json' => [
                    'variant' => array_merge($payload, [
                        'product_id' => $productId,
                    ])
                ],
            ]
        );
    
        return $response->toArray(false);
    }

    public function updateVariant(int $variantId, array $payload): array
    {
        $response = $this->httpClient->request(
            'PUT',
            "{$this->baseUrl}/variants/{$variantId}.json",
            [
                'headers' => $this->headers(),
                'json' => ['variant' => $payload],
            ]
        );

        return $response->toArray(false);
    }

    public function findProductByExternalId(string $externalId): ?array
    {
        $response = $this->httpClient->request(
            'GET',
            "{$this->baseUrl}/products.json",
            [
                'headers' => $this->headers(),
                'query' => [
                    'fields' => 'id,title,handle,tags,variants,metafields',
                    'limit' => 250
                ]
            ]
        );
    
        $data = $response->toArray(false);
    
        foreach ($data['products'] as $product) {
            // match handle
            if ($product['handle'] === strtolower($externalId)) {
                return $product;
            }
    
            // match metafield (if you later store it)
            if (!empty($product['metafields'])) {
                foreach ($product['metafields'] as $mf) {
                    if ($mf['key'] === 'externalId' && $mf['value'] === $externalId) {
                        return $product;
                    }
                }
            }
        }
    
        return null;
    }

    private function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $this->accessToken,
        ];
    }
}
