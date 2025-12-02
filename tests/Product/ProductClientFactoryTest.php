<?php

namespace App\Tests\Product;

use App\Product\Factory\ProductClientFactory;
use App\Product\Client\ProductRestClient;
use App\Product\Client\ProductGraphqlClient;
use App\Product\Client\ProductClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProductClientFactoryTest extends TestCase
{
    private function makeRestClient(): ProductRestClient
    {
        $mockHttp = $this->createMock(HttpClientInterface::class);
        return new ProductRestClient(
            httpClient: $mockHttp,
            shopDomain: 'test.myshopify.com',
            accessToken: 'dummy-token',
            apiVersion: '2023-10'
        );
    }

    private function makeGraphqlClient(): ProductGraphqlClient
    {
        $mockHttp = $this->createMock(HttpClientInterface::class);
        return new ProductGraphqlClient(
            httpClient: $mockHttp,
            shopDomain: 'test.myshopify.com',
            accessToken: 'dummy-token',
            apiVersion: '2023-10'
        );
    }

    public function testFactoryReturnsRestClient()
    {
        $factory = new ProductClientFactory(
            restClient: $this->makeRestClient(),
            graphqlClient: $this->makeGraphqlClient(),
            driver: 'rest'
        );

        $client = $factory->create();

        $this->assertInstanceOf(ProductClientInterface::class, $client);
        $this->assertInstanceOf(ProductRestClient::class, $client);
    }

    public function testFactoryReturnsGraphqlClient()
    {
        $factory = new ProductClientFactory(
            restClient: $this->makeRestClient(),
            graphqlClient: $this->makeGraphqlClient(),
            driver: 'graphql'
        );

        $client = $factory->create();

        $this->assertInstanceOf(ProductClientInterface::class, $client);
        $this->assertInstanceOf(ProductGraphqlClient::class, $client);
    }

    public function testFactoryThrowsOnInvalidDriver()
    {
        $factory = new ProductClientFactory(
            restClient: $this->makeRestClient(),
            graphqlClient: $this->makeGraphqlClient(),
            driver: 'invalid-driver'
        );

        $this->expectException(\InvalidArgumentException::class);
        $factory->create();
    }
}
