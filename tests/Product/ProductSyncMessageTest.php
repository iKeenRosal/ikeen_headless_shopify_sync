<?php

namespace App\Tests\Product;

use App\Product\Dto\ProductImportDto;
use App\Product\Dto\ProductVariantDto;
use App\Product\Message\ProductSyncMessage;
use PHPUnit\Framework\TestCase;

class ProductSyncMessageTest extends TestCase
{
    public function testMessageStoresProductDto()
    {
        $product = new ProductImportDto(
            externalId: 'ABC123',
            title: 'Test Product',
            description: 'A sample product',
            brand: 'Nike',
            category: 'Shoes',
            variants: [
                new ProductVariantDto(
                    sku: 'SKU-1',
                    color: 'Red',
                    size: 'M',
                    price: 49.99,
                    currency: 'USD',
                    inventory: 10
                )
            ],
            imageUrls: ['https://example.com/1.png']
        );

        $message = new ProductSyncMessage($product);

        $this->assertInstanceOf(ProductSyncMessage::class, $message);
        $this->assertInstanceOf(ProductImportDto::class, $message->product);

        // Validate DTO data passed inside the message
        $this->assertEquals('ABC123', $message->product->externalId);
        $this->assertEquals('Test Product', $message->product->title);
        $this->assertCount(1, $message->product->variants);

        $variant = $message->product->variants[0];
        $this->assertEquals('SKU-1', $variant->sku);
        $this->assertEquals(49.99, $variant->price);
    }
}
