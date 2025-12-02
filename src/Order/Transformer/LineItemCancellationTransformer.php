<?php

namespace App\Order\Transformer;

use App\Order\Dto\LineItemCancellationDto;

class LineItemCancellationTransformer
{
    public function transform(LineItemCancellationDto $dto): array
    {
        return [
            'lineitemId' => $dto->lineitemId,
            'sku'        => $dto->sku,
            'quantity'   => $dto->quantity,
        ];
    }
}
