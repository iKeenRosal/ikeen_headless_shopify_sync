<?php

namespace App\Product\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProductGraphqlClient implements ProductClientInterface
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

    public function upsertProduct(array $input): array
    {
        if (empty($input['externalId'])) {
            throw new \InvalidArgumentException("externalId is required for product upsert");
        }

        // 1. Try finding product by externalId
        $existing = $this->findProductByExternalId($input['externalId']);

        if ($existing) {
            // Existing Shopify product → UPDATE
            return $this->updateProduct(
                productId: $existing['id'],
                input: $input
            );
        }

        // Otherwise CREATE
        return $this->createProduct($input);
    }

    public function findProductByExternalId(string $externalId): ?array
    {
        $query = <<<GQL
        query findProductByExternalId(\$query: String!) {
            products(first: 1, query: \$query) {
                edges {
                    node {
                        id
                        title
                        handle
                        metafields(first: 10) {
                            edges {
                                node {
                                    namespace
                                    key
                                    value
                                }
                            }
                        }
                    }
                }
            }
        }
        GQL;

        // Shopify search syntax
        $search = "metafield:custom.externalId:$externalId";

        $response = $this->query($query, ['query' => $search]);

        $edges = $response['products']['edges'] ?? [];

        if (empty($edges)) {
            return null;
        }

        $product = $edges[0]['node'];

        return $product;
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

    /**
     * Create new product (GraphQL productCreate mutation)
     */
    public function createProduct(array $input): array
    {
        $mutation = <<<GQL
        mutation createProduct(\$input: ProductInput!) {
            productCreate(input: \$input) {
                product {
                    id
                    title
                    handle
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $response = $this->query($mutation, ['input' => $input]);

        $result = $response['productCreate'];

        if (!empty($result['userErrors'])) {
            throw new \Exception(
                "GraphQL Create Error: " . json_encode($result['userErrors'])
            );
        }

        return $result['product'];
    }

    /**
     * Update product (GraphQL productUpdate mutation)
     */
    public function updateProduct(string $productId, array $input): array
    {
        $mutation = <<<GQL
        mutation updateProduct(\$id: ID!, \$input: ProductInput!) {
            productUpdate(id: \$id, input: \$input) {
                product {
                    id
                    title
                    handle
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $response = $this->query($mutation, [
            'id' => $productId,
            'input' => $input,
        ]);

        $result = $response['productUpdate'];

        if (!empty($result['userErrors'])) {
            throw new \Exception("GraphQL Update Error: " . json_encode($result['userErrors']));
        }

        return $result['product'];
    }

    private function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $this->accessToken,
        ];
    }
}
