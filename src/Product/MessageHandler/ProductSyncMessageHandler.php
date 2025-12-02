<?php

namespace App\Product\MessageHandler;

use App\Product\Dto\ProductDto;
use App\Product\Message\ProductSyncMessage;
use App\Product\Transformer\ProductTransformer;
use App\Product\Client\ProductClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProductSyncMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private ProductClientInterface $shopify,
        private ProductTransformer $transformer
    ) {}

    public function __invoke(ProductSyncMessage $message): void
    {
        // $message->product is a ProductImportDto (external payload)
        $importDto = $message->product;

        // Convert external → internal DTO
        $product = ProductDto::fromImport($importDto);

        $this->logger->info("📦 Processing ProductSyncMessage", [
            'externalId' => $product->externalId,
            'title'      => $product->title,
        ]);

        // 1. Transform internal DTO → Shopify payload
        $shopifyPayload = $this->transformer->transform($product);

        // 2. Check if product exists on Shopify
        $existingProduct = $this->shopify->findProductByExternalId($product->externalId);

        if ($existingProduct) {
            $this->logger->info("🔄 Product already exists on Shopify, updating...", [
                'shopify_id' => $existingProduct['id'],
                'title'      => $existingProduct['title'],
            ]);

            $response = $this->shopify->updateProduct(
                shopifyId: $existingProduct['id'],
                payload: $shopifyPayload['product']
            );

            $this->logger->info("✅ Product updated on Shopify", [
                'shopify_id' => $response['product']['id']
            ]);

            return;
        }

        // 3. Otherwise create new product
        $this->logger->info("🆕 Creating new Shopify product...", [
            'externalId' => $product->externalId
        ]);

        $response = $this->shopify->createProduct($shopifyPayload);

        $createdId =
            $response['product']['id']     // REST
            ?? $response['id']             // GraphQL
            ?? null;

        $this->logger->info("🎉 New Shopify product created!", [
            'shopify_id' => $createdId,
        ]);
    }
}
