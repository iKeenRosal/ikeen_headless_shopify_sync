<?php

namespace App\Tests\Order\Transformer;

use App\Order\Dto\TrackingInfoDto;
use App\Order\Transformer\TrackingInfoTransformer;
use PHPUnit\Framework\TestCase;

class TrackingInfoTransformerTest extends TestCase
{
    public function testTransformsTrackingInfo()
    {
        $dto = new TrackingInfoDto(
            number: 'TRACK123',
            company: 'UPS',
            url: 'https://ups.com/track/TRACK123'
        );

        $transformer = new TrackingInfoTransformer();

        $result = $transformer->transform($dto);

        $this->assertEquals('TRACK123', $result['number']);
        $this->assertEquals('UPS', $result['company']);
        $this->assertEquals('https://ups.com/track/TRACK123', $result['url']);
    }
}
