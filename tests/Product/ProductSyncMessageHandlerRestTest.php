<?php

namespace App\Tests\Product;

use App\Product\Dto\ProductImportDto;
use App\Product\Dto\ProductVariantDto;
use App\Product\Message\ProductSyncMessage;
use App\Product\MessageHandler\ProductSyncMessageHandler;
use App\Product\Transformer\ProductTransformer;
use App\Product\Client\ProductClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProductSyncMessageHandlerRestTest extends TestCase
{
    protected function setUp(): void
    {
        // Override env for this test only
        putenv("SHOPIFY_API_DRIVER=rest");
    }

    public function testHandlerUsesRestCreateProduct(): void
    {
        // Mock product
        $importDto = new ProductImportDto(
            externalId: 'ABC123',
            title: 'Test Hoodie',
            description: 'Desc',
            brand: 'Brand',
            category: 'Category',
            variants: [
                new ProductVariantDto(
                    sku: 'SKU123',
                    size: 'L',
                    color: 'Red',
                    price: 19.99,
                    currency: 'USD',
                    inventory: 3
                )
            ],
            imageUrls: ['https://example.com/img.jpg']
        );

        $message = new ProductSyncMessage($importDto);

        // Mock transformer
        $transformer = $this->createMock(ProductTransformer::class);
        $transformer->method('transform')
            ->willReturn(['product' => ['title' => 'Hoodie']]);

        // Mock REST client behavior
        $client = $this->createMock(ProductClientInterface::class);
        $client->expects($this->once())
            ->method('createProduct')
            ->with($this->equalTo(['product' => ['title' => 'Hoodie']])) // REST expects raw product
            ->willReturn(['product' => ['id' => 999]]);

        // Mock logger
        $logger = $this->createMock(LoggerInterface::class);

        // Create handler
        $handler = new ProductSyncMessageHandler(
            logger: $logger,
            shopify: $client,
            transformer: $transformer
        );

        // Execute
        $handler($message);

        $this->assertTrue(true);
    }
}
