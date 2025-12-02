<?php

namespace App\Product\Dto;

class ProductDto
{
    /**
     * @param ProductVariantDto[] $variants
     * @param string[] $imageUrls
     */
    public function __construct(
        public string $externalId,
        public string $title,
        public ?string $description = null,
        public ?string $brand = null,
        public ?string $category = null,
        public array $variants = [],
        public array $imageUrls = [],
    ) {}

    public static function fromImport(ProductImportDto $import): self
    {
        return new self(
            externalId: $import->externalId,
            title: $import->title,
            description: $import->description,
            brand: $import->brand,
            category: $import->category,
            variants: $import->variants,
            imageUrls: $import->imageUrls,
        );
    }
}
