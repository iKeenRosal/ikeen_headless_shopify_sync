<?php

namespace App\Tests\Product;

use App\Product\Mapper\ProductMapper;
use App\Product\Dto\ProductImportDto;
use App\Product\Dto\ProductVariantDto;
use PHPUnit\Framework\TestCase;

class ProductMapperTest extends TestCase
{
    public function testMapCreatesProductImportDto(): void
    {
        $payload = [
            'externalId' => 'ABC123',
            'title' => 'Red Hoodie',
            'description' => 'Warm hoodie',
            'brand' => 'Nike',
            'category' => 'Hoodies',
            'imageUrls' => ['https://example.com/1.jpg'],
            'variants' => [
                [
                    'sku' => 'RH-RED-S',
                    'color' => 'Red',
                    'size' => 'S',
                    'price' => 29.99,
                    'currency' => 'USD',
                    'inventory' => 25,
                ]
            ]
        ];

        $mapper = new ProductMapper();
        $productDto = $mapper->map($payload);

        $this->assertInstanceOf(ProductImportDto::class, $productDto);
        $this->assertEquals('ABC123', $productDto->externalId);
        $this->assertCount(1, $productDto->variants);

        $firstVariant = $productDto->variants[0];

        $this->assertInstanceOf(ProductVariantDto::class, $firstVariant);
        $this->assertEquals('RH-RED-S', $firstVariant->sku);
        $this->assertEquals('Red', $firstVariant->color);
        $this->assertEquals(29.99, $firstVariant->price);
    }

    public function testMapThrowsExceptionForMissingExternalId(): void
    {
        $this->expectException(\Exception::class);

        $payload = [
            'title' => 'Oops — Missing externalId'
        ];

        $mapper = new ProductMapper();
        $mapper->map($payload);
    }
}
