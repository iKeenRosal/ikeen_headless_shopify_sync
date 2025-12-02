<?php

namespace App\Tests\Order\Transformer;

use App\Order\Dto\LineItemCancellationDto;
use App\Order\Transformer\LineItemCancellationTransformer;
use PHPUnit\Framework\TestCase;

class LineItemCancellationTransformerTest extends TestCase
{
    public function testTransformsLineItemCancellation(): void
    {
        $dto = new LineItemCancellationDto(
            lineitemId: '134343',
            sku: 'SKU123',
            quantity: 2
        );

        $transformer = new LineItemCancellationTransformer();

        $result = $transformer->transform($dto);

        $this->assertSame('134343', $result['lineitemId']);
        $this->assertSame('SKU123', $result['sku']);
        $this->assertSame(2, $result['quantity']);
    }
}
