<?php

namespace App\Tests\Product;

use App\Product\Dto\ProductDto;
use App\Product\Dto\ProductVariantDto;
use App\Product\Transformer\ProductTransformer;
use PHPUnit\Framework\TestCase;

class ProductTransformerTest extends TestCase
{
    public function testTransformCreatesShopifyPayload()
    {
        $dto = new ProductDto(
            externalId: 'ABC123',
            title: 'Red Hoodie',
            description: 'Warm hoodie',
            brand: 'Nike',
            category: 'Hoodies',
            variants: [
                new ProductVariantDto(
                    sku: 'RH-RED-S',
                    color: 'Red',
                    size: 'S',
                    price: 29.99,
                    currency: 'USD',
                    inventory: 10
                )
            ],
            imageUrls: ['https://example.com/img.jpg']
        );

        $transformer = new ProductTransformer();
        $payload = $transformer->transform($dto);

        $this->assertArrayHasKey('product', $payload);
        $shopifyProduct = $payload['product'];

        $this->assertEquals('Red Hoodie', $shopifyProduct['title']);
        $this->assertEquals('Warm hoodie', $shopifyProduct['body_html']);
        $this->assertEquals('Nike', $shopifyProduct['vendor']);
        $this->assertEquals('Hoodies', $shopifyProduct['product_type']);

        $this->assertCount(1, $shopifyProduct['variants']);
        $variant = $shopifyProduct['variants'][0];

        $this->assertEquals('RH-RED-S', $variant['sku']);
        $this->assertEquals('29.99', $variant['price']);
        $this->assertEquals('Red', $variant['option1']);
        $this->assertEquals('S', $variant['option2']);
        $this->assertEquals(10, $variant['inventory_quantity']);
    }
}
