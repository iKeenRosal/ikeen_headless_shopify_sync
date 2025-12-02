<?php

namespace App\Order\Transformer;

use App\Order\Dto\PartialFulfillmentItemDto;

class PartialFulfillmentItemTransformer
{
    public function transform(PartialFulfillmentItemDto $dto): array
    {
        return [
            'lineItemId' => $dto->lineItemId,
            'quantity'   => $dto->quantity,
        ];
    }
}
