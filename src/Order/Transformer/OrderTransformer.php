<?php

namespace App\Transformer;

use App\Dto\OrderDto;

class OrderTransformer
{
    /**
     * Transforms client order data to shopify order data
     */
    public function transform(OrderDto $order): array
    {
        return [
            'order' => [
                'title' => $order->title,
                'body_html' => $order->description ?? '',
                'vendor' => $order->brand ?? 'Unknown',
                'order_type' => $order->category ?? 'Uncategorized',

                // Shopify always expects ["src" => "..."]
                'images' => array_map(
                    fn($url) => ['src' => $url],
                    $order->imageUrls
                ),

                // REQUIRED: Declare option names when using option1 / option2
                'options' => $this->buildOptions($order),

                // Build variant payloads
                'variants' => array_map(
                    fn($v) => $this->mapVariant($v),
                    $order->variants
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

    private function buildOptions(OrderDto $order): array
    {
        $hasColor = false;
        $hasSize = false;

        foreach ($order->variants as $variant) {
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
