<?php

namespace App\Product\Service;

use App\Product\Client\ProductRestClient;
use App\Product\Dto\ProductDto;
use App\Product\Dto\ProductImportDto;
use Psr\Log\LoggerInterface;

class ProductSyncService
{
    public function __construct(
        private ProductRestClient $client,
        private LoggerInterface $logger
    ) {}

    /**
     * Main sync entry point
     */
    public function sync(ProductImportDto $product): array
    {
        $this->logger->info("Starting sync for product {$product->externalId}");

        // Convert DTO → Shopify payload
        $payload = $this->buildShopifyPayload($product);

        // Check if product exists on Shopify
        $existing = $this->client->getProductByExternalId($product->externalId);

        if ($existing) {
            $shopifyId = $existing['id'];
            $this->logger->info("Updating existing Shopify product ID: $shopifyId");

            $response = $this->client->updateProduct($shopifyId, $payload);
        } else {
            $this->logger->info("Creating new Shopify product");

            $response = $this->client->createProduct($payload);
        }

        return [
            'success' => true,
            'externalId' => $product->externalId,
            'shopify_response' => $response
        ];
    }

    /**
     * Convert DTO → Shopify-compatible API structure
     */
    private function buildShopifyPayload(ProductImportDto $product): array
    {
        return [
            'title' => $product->title,
            'body_html' => $product->description ?? '',
            'vendor' => $product->brand ?? 'Unknown',
            'product_type' => $product->category ?? null,
            'imageUrls' => array_map(fn($url) => ['src' => $url], $product->imageUrls),

            // Variants
            'variants' => array_map(function ($v) {
                return [
                    'sku' => $v->sku,
                    'price' => $v->price,
                    'option1' => $v->color ?? 'Default',
                    'option2' => $v->size ?? null,
                    'inventory_quantity' => $v->inventory ?? 0,
                ];
            }, $product->variants),

            // Shopify requires options if using option1/option2
            'options' => [
                ['name' => 'Color'],
                ['name' => 'Size'],
            ],
        ];
    }
}
