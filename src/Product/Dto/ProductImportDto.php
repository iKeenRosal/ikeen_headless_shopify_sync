<?php

namespace App\Product\Dto;

class ProductImportDto
{
    public function __construct(
        public string $externalId,
        public string $title,
        public ?string $description = null,
        public ?string $brand = null,
        public ?string $category = null,
        public array $variants = [],
        public array $imageUrls = [],
    ) {}
}
