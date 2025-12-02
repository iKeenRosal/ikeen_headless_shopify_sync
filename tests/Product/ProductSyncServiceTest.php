<?php

namespace App\Tests\Product;

use App\Product\Dto\ProductImportDto;
use App\Product\Dto\ProductVariantDto;
use App\Product\Client\ProductRestClient;
use App\Product\Service\ProductSyncService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProductSyncServiceTest extends TestCase
{
    private function makeProduct(): ProductImportDto
    {
        return new ProductImportDto(
            externalId: "ABC123",
            title: "Test Hoodie",
            description: "Soft hoodie",
            brand: "Nike",
            category: "Hoodies",
            imageUrls: ["https://example.com/img1.jpg"],
            variants: [
                new ProductVariantDto(
                    sku: "HD-RED-S",
                    color: "Red",
                    size: "S",
                    price: 29.99,
                    currency: "USD",
                    inventory: 20
                )
            ]
        );
    }

    public function testCreatesNewShopifyProductWhenNotExisting()
    {
        $product = $this->makeProduct();

        // Mock logger
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())->method('info');

        // Mock Shopify REST client
        $client = $this->createMock(ProductRestClient::class);

        // getProductByExternalId should return null → meaning product does NOT exist
        $client->expects($this->once())
            ->method('getProductByExternalId')
            ->with("ABC123")
            ->willReturn(null);

        // createProduct should be called for new products
        $client->expects($this->once())
            ->method('createProduct')
            ->with($this->callback(function ($payload) {
                return $payload['title'] === "Test Hoodie"; // minimal assertion
            }))
            ->willReturn([
                'product' => ['id' => 1000, 'title' => 'Test Hoodie']
            ]);

        $service = new ProductSyncService($client, $logger);

        $result = $service->sync($product);

        $this->assertTrue($result['success']);
        $this->assertEquals("ABC123", $result['externalId']);
        $this->assertEquals(1000, $result['shopify_response']['product']['id']);
    }

    public function testUpdatesExistingProduct()
    {
        $product = $this->makeProduct();

        // Mock logger
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())->method('info');

        $client = $this->createMock(ProductRestClient::class);

        // Product already exists in Shopify
        $client->expects($this->once())
            ->method('getProductByExternalId')
            ->willReturn([
                'id' => 555,
                'title' => 'Old Title'
            ]);

        // updateProduct should be called instead of createProduct
        $client->expects($this->once())
            ->method('updateProduct')
            ->with(
                555,
                $this->callback(function ($payload) {
                    return $payload['title'] === "Test Hoodie";
                })
            )
            ->willReturn([
                'product' => ['id' => 555, 'title' => 'Updated Title']
            ]);

        // createProduct must NOT be called
        $client->expects($this->never())->method('createProduct');

        $service = new ProductSyncService($client, $logger);

        $result = $service->sync($product);

        $this->assertTrue($result['success']);
        $this->assertEquals(555, $result['shopify_response']['product']['id']);
        $this->assertEquals("ABC123", $result['externalId']);
    }
}
