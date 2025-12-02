<?php

namespace App\Product\Factory;

use App\Product\Client\ProductClientInterface;
use App\Product\Client\ProductGraphqlClient;
use App\Product\Client\ProductRestClient;

class ProductClientFactory
{
    private string $driver;

    public function __construct(
        private ProductRestClient $restClient,
        private ProductGraphqlClient $graphqlClient,
        string $driver
    ) {
        $this->driver = strtolower($driver);
    }

    public function create(): ProductClientInterface
    {
        return match ($this->driver) {
            'rest' => $this->restClient,
            'graphql' => $this->graphqlClient,
            default => throw new \InvalidArgumentException(
                "Invalid SHOPIFY_API_DRIVER '{$this->driver}'. Expected 'rest' or 'graphql'."
            ),
        };
    }
}
