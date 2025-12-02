<?php

namespace App\Order\Service;

use App\Order\Client\OrderClientInterface;
use App\Order\Mapper\OrderMapper;
use App\Order\Message\OrderSyncMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class OrderSyncService
{
    public function __construct(
        private OrderClientInterface $client,
        private OrderMapper $mapper,
        private MessageBusInterface $bus,
        private LoggerInterface $logger
    ) {}

    /**
     * Pulls orders from Shopify (REST or GraphQL), maps them,
     * dispatches async messages for background processing.
     */
    public function syncWindow(int $minHours = 1, int $maxHours = 72): int
    {
        $this->logger->info("⏳ Starting order sync for window: {$minHours}-{$maxHours} hours");

        $orders = $this->client->getOrders($minHours, $maxHours);

        $count = 0;

        foreach ($orders as $rawOrder) {
            try {
                $importDto = $this->mapper->mapImport($rawOrder);
                $this->bus->dispatch(new OrderSyncMessage($importDto));

                $this->logger->info("📦 Queued order", [
                    'externalId' => $importDto->externalId
                ]);

                $count++;

            } catch (\Throwable $e) {
                $this->logger->error("❌ Failed to map or queue order", [
                    'error' => $e->getMessage(),
                    'payload' => $rawOrder
                ]);
            }
        }

        $this->logger->info("🎉 Finished syncing orders. Total queued: {$count}");

        return $count;
    }
}
