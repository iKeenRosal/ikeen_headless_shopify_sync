<?php

namespace App\Product\Transformer;

use App\Product\Dto\ProductDto;

class ProductTransformer
{
    /**
     * Transforms client product data to shopify product data
     */
    public function transform(ProductDto $product): array
    {
        return [
            'product' => [
                'title' => $product->title,
                'body_html' => $product->description ?? '',
                'vendor' => $product->brand ?? 'Unknown',
                'product_type' => $product->category ?? 'Uncategorized',

                // Shopify always expects ["src" => "..."]
                'images' => array_map(
                    fn($url) => ['src' => $url],
                    $product->imageUrls
                ),

                // REQUIRED: Declare option names when using option1 / option2
                'options' => $this->buildOptions($product),

                // Build variant payloads
                'variants' => array_map(
                    fn($v) => $this->mapVariant($v),
                    $product->variants
                ),
            ]
        ];
    }

    private function mapVariant($v): array
    {
        return array_filter([
            'sku' => $v->sku,
            'price' => number_format($v->price, 2, '.', ''),
            'option1' => $v->color ?? 'Default',
            'option2' => $v->size ?? null,
            'inventory_quantity' => $v->inventory ?? 0,
        ], fn($value) => $value !== null);
    }

    private function buildOptions(ProductDto $product): array
    {
        $hasColor = false;
        $hasSize = false;

        foreach ($product->variants as $variant) {
            if (!empty($variant->color)) {
                $hasColor = true;
            }
            if (!empty($variant->size)) {
                $hasSize = true;
            }
        }

        $options = [];

        if ($hasColor) {
            $options[] = ['name' => 'Color'];
        }

        if ($hasSize) {
            $options[] = ['name' => 'Size'];
        }

        return $options;
    }
}
