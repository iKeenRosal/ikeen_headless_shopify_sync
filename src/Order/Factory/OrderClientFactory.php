<?php

namespace App\Order\Factory;

use App\Order\Client\OrderClientInterface;
use App\Order\Client\OrderGraphqlClient;
use App\Order\Client\OrderRestClient;

class OrderClientFactory
{
    private string $driver;

    public function __construct(
        private OrderRestClient $restClient,
        private OrderGraphqlClient $graphqlClient,
        string $driver
    ) {
        $this->driver = strtolower($driver);
    }

    public function create(): OrderClientInterface
    {
        return match ($this->driver) {
            'rest' => $this->restClient,
            default => $this->graphqlClient, // Shopify preferred
        };
    }
}
