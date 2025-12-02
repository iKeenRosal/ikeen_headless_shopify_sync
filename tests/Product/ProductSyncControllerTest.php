<?php

namespace App\Tests\Controller;

use App\Product\Controller\ProductSyncController;
use App\Product\Dto\ProductImportDto;
use App\Product\Mapper\ProductMapper;
use App\Product\Message\ProductSyncMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductSyncControllerTest extends WebTestCase
{
    public function testImportSingleProductQueuesMessage()
    {
        $mapper = $this->createMock(ProductMapper::class);
        $bus    = $this->createMock(MessageBusInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $payload = [
            'externalId' => 'ABC123',
            'title'      => 'Test Hoodie',
        ];

        $productDto = new ProductImportDto(
            externalId: 'ABC123',
            title: 'Test Hoodie',
            description: null,
            brand: null,
            category: null,
            variants: [],
            imageUrls: [],
        );

        $mapper->method('map')->with($payload)->willReturn($productDto);

        $bus->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($msg) use ($productDto) {
                    return $msg instanceof ProductSyncMessage
                        && $msg->product === $productDto;
                })
            )
            ->willReturnCallback(fn($msg) => new Envelope($msg));

        $controller = new ProductSyncController($mapper, $bus, $logger);

        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $response = $controller->import($request);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals('queued', $data['status']);
        $this->assertEquals(1, $data['count']);
    }

    public function testImportMultipleProductsQueuesMessages()
    {
        $mapper = $this->createMock(ProductMapper::class);
        $bus    = $this->createMock(MessageBusInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $payload = [
            'products' => [
                ['externalId' => 'P1', 'title' => 'Product 1'],
                ['externalId' => 'P2', 'title' => 'Product 2'],
            ]
        ];

        $dto1 = new ProductImportDto('P1', 'Product 1');
        $dto2 = new ProductImportDto('P2', 'Product 2');

        // Map correct DTO for each item
        $mapper->method('map')
            ->willReturnCallback(function ($raw) use ($dto1, $dto2) {
                return $raw['externalId'] === 'P1' ? $dto1 : $dto2;
            });

        // Expect two dispatch calls
        $bus->expects($this->exactly(2))
            ->method('dispatch')
            ->with(
                $this->callback(fn(ProductSyncMessage $msg) =>
                    in_array($msg->product->externalId, ['P1', 'P2'])
                )
            )
            ->willReturnCallback(fn($msg) => new Envelope($msg));

        $controller = new ProductSyncController($mapper, $bus, $logger);

        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $response = $controller->import($request);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('queued', $data['status']);
        $this->assertEquals(2, $data['count']);
    }

    public function testInvalidJsonReturns400()
    {
        $mapper = $this->createMock(ProductMapper::class);
        $bus    = $this->createMock(MessageBusInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $controller = new ProductSyncController($mapper, $bus, $logger);

        $request = new Request([], [], [], [], [], [], 'INVALID{JSON');

        $response = $controller->import($request);

        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }
}
