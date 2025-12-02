<?php

namespace App\Tests\Order\Transformer;

use App\Order\Dto\PartialFulfillmentItemDto;
use App\Order\Transformer\PartialFulfillmentItemTransformer;
use PHPUnit\Framework\TestCase;

class PartialFulfillmentItemTransformerTest extends TestCase
{
    public function testTransformsPartialFulfillmentItem()
    {
        $dto = new PartialFulfillmentItemDto(
            lineItemId: 'gid://shopify/LineItem/555',
            quantity: 1
        );

        $transformer = new PartialFulfillmentItemTransformer();

        $result = $transformer->transform($dto);

        $this->assertEquals('gid://shopify/LineItem/555', $result['lineItemId']);
        $this->assertEquals(1, $result['quantity']);
    }
}
