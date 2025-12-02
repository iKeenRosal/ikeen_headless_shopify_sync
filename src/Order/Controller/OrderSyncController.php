<?php

namespace App\Order\Controller;

use App\Order\Mapper\OrderMapper;
use App\Order\Message\OrderSyncMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class OrderSyncController extends AbstractController
{
    public function __construct(
        private OrderMapper $mapper,
        private MessageBusInterface $bus,
        private LoggerInterface $logger
    ) {}

    #[Route('/sync/order', name: 'sync_order', methods: ['POST'])]
    public function sync(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);

            if (!$payload) {
                return new JsonResponse(['error' => 'Invalid JSON payload'], 400);
            }

            $this->logger->info("🧾 OrderSyncController: Order queued for sync", [
                'payload' => $payload
            ]);

            // ALWAYS initialize
            $orders = [];

            // CASE 1 — multiple
            if (isset($payload['orders']) && is_array($payload['orders'])) {
                foreach ($payload['orders'] as $rawOrder) {
                    $orders[] = $this->mapper->map($rawOrder);
                }
            }
            // CASE 2 — single
            else {
                $orders[] = $this->mapper->map($payload);
            }

            // Dispatch each order
            foreach ($orders as $orderDto) {
                $this->bus->dispatch(new OrderSyncMessage($orderDto));
            }

            return new JsonResponse([
                'status'      => 'queued',
                'count'       => count($orders),
                'externalIds' => array_map(fn($o) => $o->externalId, $orders),
            ], 202);

        } catch (\Throwable $e) {
            $this->logger->error("Order sync error: " . $e->getMessage(), [
                'exception' => $e
            ]);

            return new JsonResponse([
                'error'   => 'Order sync failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
