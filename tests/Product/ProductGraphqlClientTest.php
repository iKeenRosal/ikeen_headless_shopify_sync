<?php

namespace App\Tests\Product;

use App\Product\Client\ProductClientInterface;
use App\Product\Client\ProductGraphqlClient;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProductGraphqlClientTest extends TestCase
{
    protected function setUp(): void
    {
        putenv("SHOPIFY_API_DRIVER=graphql");
    }

    private function mockResponse(array $data): ResponseInterface
    {
        $mock = $this->createMock(ResponseInterface::class);
        $mock->method('toArray')->willReturn($data);
        return $mock;
    }

    private function makeClient(ResponseInterface $response): ProductGraphqlClient
    {
        $mockHttp = $this->createMock(HttpClientInterface::class);
        $mockHttp
            ->method('request')
            ->willReturn($response);

        return new ProductGraphqlClient(
            httpClient: $mockHttp,
            shopDomain: 'test.myshopify.com',
            accessToken: 'dummy-token',
            apiVersion: '2023-10'
        );
    }

    public function testQuerySuccess()
    {
        $response = $this->mockResponse([
            'data' => ['ok' => true]
        ]);

        $client = $this->makeClient($response);

        $result = $client->query('query {}');

        $this->assertEquals(['ok' => true], $result);
    }

    public function testQueryThrowsWhenErrorsReturned()
    {
        $response = $this->mockResponse([
            'errors' => [['message' => 'Something broke']]
        ]);

        $client = $this->makeClient($response);

        $this->expectException(\Exception::class);
        $client->query('query {}');
    }

    public function testCreateProductSuccess()
    {
        $response = $this->mockResponse([
            'data' => [
                'productCreate' => [
                    'product' => [
                        'id' => 'gid://shopify/Product/123',
                        'title' => 'Test Product',
                        'handle' => 'test-product',
                    ],
                    'userErrors' => []
                ]
            ]
        ]);

        $client = $this->makeClient($response);

        $result = $client->createProduct([
            'title' => 'Test Product'
        ]);

        $this->assertEquals('gid://shopify/Product/123', $result['id']);
        $this->assertEquals('Test Product', $result['title']);
    }

    public function testCreateProductThrowsOnUserErrors()
    {
        $response = $this->mockResponse([
            'data' => [
                'productCreate' => [
                    'product' => null,
                    'userErrors' => [
                        ['message' => 'Invalid input']
                    ]
                ]
            ]
        ]);

        $client = $this->makeClient($response);

        $this->expectException(\Exception::class);
        $client->createProduct(['title' => 'Bad']);
    }

    public function testUpdateProductSuccess()
    {
        $response = $this->mockResponse([
            'data' => [
                'productUpdate' => [
                    'product' => [
                        'id' => 'gid://shopify/Product/999',
                        'title' => 'Updated Product',
                        'handle' => 'updated-product'
                    ],
                    'userErrors' => []
                ]
            ]
        ]);

        $client = $this->makeClient($response);

        $result = $client->updateProduct('gid://shopify/Product/999', [
            'title' => 'Updated Product'
        ]);

        $this->assertEquals('gid://shopify/Product/999', $result['id']);
        $this->assertEquals('Updated Product', $result['title']);
    }

    public function testUpdateProductThrowsOnErrors()
    {
        $response = $this->mockResponse([
            'data' => [
                'productUpdate' => [
                    'product' => null,
                    'userErrors' => [
                        ['message' => 'Cannot update']
                    ]
                ]
            ]
        ]);

        $client = $this->makeClient($response);

        $this->expectException(\Exception::class);
        $client->updateProduct('gid://shopify/Product/999', []);
    }
}
