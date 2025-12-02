<?php

namespace App\Tests\Product;

use App\Product\Client\ProductGraphqlClient;
use PHPUnit\Framework\TestCase;

class ProductGraphqlClientUpsertTest extends TestCase
{
    private function mockClient(): ProductGraphqlClient
    {
        return $this->getMockBuilder(ProductGraphqlClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findProductByExternalId','createProduct','updateProduct'])
            ->getMock();
    }

    public function testUpsertCallsUpdateWhenProductExists()
    {
        $client = $this->mockClient();

        $client->method('findProductByExternalId')
            ->willReturn(['id' => 'gid://shopify/Product/123']);

        $client->expects($this->once())
            ->method('updateProduct')
            ->with(
                $this->equalTo('gid://shopify/Product/123'),
                $this->arrayHasKey('title')
            )
            ->willReturn(['id' => 'gid://shopify/Product/123']);

        $input = [
            'externalId' => 'ABC123',
            'title' => 'Updated Product'
        ];

        $client->upsertProduct($input);
    }

    public function testUpsertCallsCreateWhenProductDoesNotExist()
    {
        $client = $this->mockClient();

        $client->method('findProductByExternalId')
            ->willReturn(null);

        $client->expects($this->once())
            ->method('createProduct')
            ->with($this->arrayHasKey('title'))
            ->willReturn(['id' => 'new-product-id']);

        $input = [
            'externalId' => 'ABC123',
            'title' => 'New Product'
        ];

        $client->upsertProduct($input);
    }

    public function testUpsertThrowsIfMissingExternalId()
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = $this->mockClient();

        $client->upsertProduct(['title' => 'No external ID']);
    }
}
