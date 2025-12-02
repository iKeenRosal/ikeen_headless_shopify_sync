<?php

namespace App\Order\Dto;

class LineItemCancellationImportDto
{
    public function __construct(
        public string $lineitemId,
        public string $sku,
        public int $quantity,
        public ?string $reason = null,
        public array $raw = [] // preserve original incoming payload
    ) {}
}
