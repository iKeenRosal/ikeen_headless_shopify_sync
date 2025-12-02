<?php

namespace App\Order\Transformer;

use App\Order\Dto\TrackingInfoDto;

class TrackingInfoTransformer
{
    public function transform(TrackingInfoDto $dto): array
    {
        return [
            'number'  => $dto->number,
            'company' => $dto->company,
            'url'     => $dto->url
        ];
    }
}
