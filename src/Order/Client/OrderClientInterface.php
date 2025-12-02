<?php

namespace App\Order\Client;

interface OrderClientInterface
{
    public function upsertOrder(array $payload): array;

    public function findOrderByExternalId(string $externalId): ?array;

    public function getOrders(int $minHours, int $maxHours): array;
    
    public function getOrderById(string $shopifyId): ?array;

    public function updateOrder(string $shopifyId, array $payload): array;

    public function cancelOrder(string $shopifyId): array;

    public function cancelLineItems(string $orderId, array $lineItemPayload): array;

    public function createFulfillment(string $orderId, array $fulfillmentPayload): array;

    public function createPartialFulfillment(string $orderId, array $lineItems): array;

    public function createRefund(string $orderId, array $refundPayload): array;

    public function updateTracking(string $fulfillmentId, array $trackingData): array;


    
    // Maybe later

    // public function updateOrderMetafields(string $orderId, array $metafields): array;
    // public function getFulfillmentOrders(string $orderId): array;
    // public function acceptFulfillmentOrder(string $fulfillmentOrderId): array;
    // public function rejectFulfillmentOrder(string $fulfillmentOrderId, string $reason): array;

    // public function capturePayment(string $orderId, float $amount): array;
    // public function voidPayment(string $orderId): array;



}

