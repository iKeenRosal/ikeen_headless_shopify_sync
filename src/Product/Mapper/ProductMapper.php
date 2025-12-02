<?php

namespace App\Product\Mapper;

use App\Product\Dto\ProductImportDto;
use App\Product\Dto\ProductVariantDto;

class ProductMapper
{
    /**
     * Map incoming payload into ProductImportDto
     * Payload may contain:
     *  - "products" (array of products)
     *  - OR a single product directly
     */
    public function map(array $payload): ProductImportDto
    {
        // If payload contains multiple products → map *first*
        if (isset($payload['products']) && is_array($payload['products'])) {
            if (!isset($payload['products'][0])) {
                throw new \Exception('Products array is empty');
            }
            return $this->mapSingleProduct($payload['products'][0]);
        }

        // Normal single-product mapping
        return $this->mapSingleProduct($payload);
    }

    /**
     * Maps a single product into ProductImportDto
     */
    private function mapSingleProduct(array $product): ProductImportDto
    {
        $externalId = $product['externalId']
            ?? throw new \Exception('Missing externalId');

        $title = $product['title']
            ?? throw new \Exception('Missing title');

        $variants = [];

        if (!empty($product['variants']) && is_array($product['variants'])) {
            foreach ($product['variants'] as $variant) {
                $variants[] = new ProductVariantDto(
                    sku: $variant['sku'] ?? '',
                    color: $variant['color'] ?? null,
                    size: $variant['size'] ?? null,
                    price: $variant['price'] ?? 0,
                    currency: $variant['currency'] ?? 'USD',
                    inventory: $variant['inventory'] ?? null,
                );
            }
        }

        return new ProductImportDto(
            externalId: $externalId,
            title: $title,
            description: $product['description'] ?? null,
            brand: $product['brand'] ?? null,
            category: $product['category'] ?? null,
            variants: $variants,
            imageUrls: $product['imageUrls'] ?? []
        );
    }
}
