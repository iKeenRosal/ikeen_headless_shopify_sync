<?php

namespace App\Order\Message;

use App\Order\Dto\OrderImportDto;

class OrderSyncMessage
{
    public function __construct(
        public OrderImportDto $order
    ) {}
}
