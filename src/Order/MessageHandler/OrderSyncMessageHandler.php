<?php

namespace App\Order\MessageHandler;

use App\Order\Message\OrderSyncMessage;
use App\Order\Transformer\OrderTransformerInterface;
use App\Order\Client\OrderClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class OrderSyncMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private OrderClientInterface $client,
        private OrderTransformerInterface $transformer,
    ) {}

    public function __invoke(OrderSyncMessage $message): void
    {
        $orderImportDto = $message->order;

        $this->logger->info("🧾 Processing OrderSyncMessage", [
            'externalOrderId' => $orderImportDto->externalId
        ]);

        // Convert import data → internal OrderDto
        $orderDto = $this->transformer->fromImport($orderImportDto);

        // Convert to Shopify-ready payload
        $shopifyPayload = $this->transformer->toShopify($orderDto);

        // Upsert (update or create)
        $response = $this->client->upsertOrder($shopifyPayload);

        $this->logger->info("📦 Order synced to Shopify", [
            'shopify_id' => $response['id'] ?? null
        ]);
    }
}
