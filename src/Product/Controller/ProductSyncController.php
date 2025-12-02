<?php

namespace App\Product\Controller;

use App\Product\Mapper\ProductMapper;
use App\Product\Message\ProductSyncMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProductSyncController extends AbstractController
{
    public function __construct(
        private ProductMapper $mapper,
        private MessageBusInterface $bus,
        private LoggerInterface $logger
    ) {}

    #[Route('/api/products/import', name: 'import_product', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);

            if (!$payload) {
                return new JsonResponse(['error' => 'Invalid JSON'], 400);
            }

            $this->logger->info("🧾 ProductSyncController: Product queued for sync", ['payload' => $payload]);

            $products = [];

            // CASE 1 — "products": [...]
            if (isset($payload['products']) && is_array($payload['products'])) {
                foreach ($payload['products'] as $rawProduct) {
                    $products[] = $this->mapper->map($rawProduct);
                }
            } 
            // CASE 2 — SINGLE PRODUCT
            else {
                $products[] = $this->mapper->map($payload);
            }

            // Dispatch each product individually
            foreach ($products as $productDto) {
                $this->bus->dispatch(new ProductSyncMessage($productDto));
            }

            return new JsonResponse([
                'status' => 'queued',
                'count'  => count($products)
            ]);

        } catch (\Throwable $e) {
            $this->logger->error("Order sync error: " . $e->getMessage(), [
                'exception' => $e
            ]);

            return new JsonResponse([
                'error' => 'Order sync failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
