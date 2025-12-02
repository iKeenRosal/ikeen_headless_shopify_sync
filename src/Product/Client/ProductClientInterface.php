<?php

namespace App\Product\Client;

interface ProductClientInterface
{
    public function createProduct(array $payload): array;

    public function updateProduct(string $productId, array $payload): array;

    public function upsertProduct(array $payload): array;

    public function findProductByExternalId(string $externalId): ?array;
}
