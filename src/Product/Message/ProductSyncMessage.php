<?php

namespace App\Product\Message;

use App\Product\Dto\ProductImportDto;

class ProductSyncMessage
{
    public function __construct(
        public ProductImportDto $product
    ) {}
}
