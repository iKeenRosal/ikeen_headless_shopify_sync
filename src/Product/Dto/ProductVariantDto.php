<?php

namespace App\Product\Dto;

class ProductVariantDto
{
    public function __construct(
        public string $sku,
        public ?string $color = null,
        public ?string $size = null,
        public float $price = 0.0,
        public string $currency = 'USD',
        public ?int $inventory = null,
    ) {}
}
