<?php

namespace App\Tests\Product;

use App\Product\Client\ProductRestClient;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProductRestClientTest extends TestCase
{
    protected function setUp(): void
    {
        putenv("SHOPIFY_API_DRIVER=rest");
    }

    private function mockResponse(array $data): ResponseInterface
    {
        $mock = $this->createMock(ResponseInterface::class);
        $mock->method('toArray')->willReturn($data);

        return $mock;
    }

    private function makeClient(ResponseInterface $response): ProductRestClient
    {
        $mockHttp = $this->createMock(HttpClientInterface::class);
        $mockHttp
            ->method('request')
            ->willReturn($response);

        return new ProductRestClient(
            httpClient: $mockHttp,
            shopDomain: 'test-shop.myshopify.com',
            accessToken: 'token123',
            apiVersion: '2023-10'
        );
    }

    public function testCreateProduct()
    {
        $response = $this->mockResponse([
            'product' => [
                'id' => 1001,
                'title' => 'Test Product'
            ]
        ]);

        $client = $this->makeClient($response);

        $result = $client->createProduct([
            'title' => 'Test Product'
        ]);

        $this->assertEquals(1001, $result['product']['id']);
        $this->assertEquals('Test Product', $result['product']['title']);
    }

    public function testUpdateProduct()
    {
        $response = $this->mockResponse([
            'product' => [
                'id' => 1001,
                'title' => 'Updated Product'
            ]
        ]);

        $client = $this->makeClient($response);

        $result = $client->updateProduct(1001, [
            'title' => 'Updated Product'
        ]);

        $this->assertEquals('Updated Product', $result['product']['title']);
    }

    public function testGetProductByExternalIdReturnsMatch()
    {
        $response = $this->mockResponse([
            'products' => [
                [
                    'id' => 999,
                    'title' => 'ABC123',
                    'handle' => 'abc123'
                ]
            ]
        ]);

        $client = $this->makeClient($response);

        $result = $client->getProductByExternalId('ABC123');

        $this->assertNotNull($result);
        $this->assertEquals(999, $result['id']);
    }

    public function testGetProductByExternalIdReturnsNullIfNotFound()
    {
        $response = $this->mockResponse([
            'products' => []
        ]);

        $client = $this->makeClient($response);

        $result = $client->getProductByExternalId('NOTFOUND');

        $this->assertNull($result);
    }

    public function testCreateVariant()
    {
        $response = $this->mockResponse([
            'variant' => [
                'id' => 2001,
                'sku' => 'SKU-1'
            ]
        ]);

        $client = $this->makeClient($response);

        $result = $client->createVariant(1001, [
            'sku' => 'SKU-1'
        ]);

        $this->assertEquals(2001, $result['variant']['id']);
        $this->assertEquals('SKU-1', $result['variant']['sku']);
    }

    public function testUpdateVariant()
    {
        $response = $this->mockResponse([
            'variant' => [
                'id' => 2001,
                'sku' => 'UPDATED-SKU'
            ]
        ]);

        $client = $this->makeClient($response);

        $result = $client->updateVariant(2001, [
            'sku' => 'UPDATED-SKU'
        ]);

        $this->assertEquals('UPDATED-SKU', $result['variant']['sku']);
    }

    public function testFindProductByExternalIdMatchesHandle()
    {
        $response = $this->mockResponse([
            'products' => [
                ['id' => 123, 'handle' => 'abc123', 'title' => 'Test']
            ]
        ]);

        $client = $this->makeClient($response);
        $result = $client->findProductByExternalId('abc123');

        $this->assertNotNull($result);
        $this->assertEquals(123, $result['id']);
    }

    public function testFindProductByExternalIdMatchesMetafield()
    {
        $response = $this->mockResponse([
            'products' => [
                [
                    'id' => 321,
                    'handle' => 'something',
                    'metafields' => [
                        ['key' => 'externalId', 'value' => 'EXT-999']
                    ]
                ]
            ]
        ]);

        $client = $this->makeClient($response);
        $result = $client->findProductByExternalId('EXT-999');

        $this->assertNotNull($result);
        $this->assertEquals(321, $result['id']);
    }

    public function testFindProductByExternalIdReturnsNull()
    {
        $response = $this->mockResponse([
            'products' => [
                [
                    'id' => 999,
                    'handle' => 'something',
                    'metafields' => []
                ]
            ]
        ]);

        $client = $this->makeClient($response);

        $result = $client->findProductByExternalId('nope');

        $this->assertNull($result);
    }
}
