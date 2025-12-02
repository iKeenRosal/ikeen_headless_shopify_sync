<?php

namespace App\Tests\Product;

use App\Product\Dto\ProductImportDto;
use App\Product\Dto\ProductVariantDto;
use App\Product\Message\ProductSyncMessage;
use App\Product\MessageHandler\ProductSyncMessageHandler;
use App\Product\Transformer\ProductTransformer;
use App\Product\Client\ProductClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProductSyncMessageHandlerGraphqlTest extends TestCase
{
    protected function setUp(): void
    {
        // Override env for this test only
        putenv("SHOPIFY_API_DRIVER=graphql");
    }

    public function testHandlerUsesGraphqlCreateProduct(): void
    {
        $importDto = new ProductImportDto(
            externalId: "ABC123",
            title: "Hoodie",
            variants: [],
            imageUrls: []
        );

        $message = new ProductSyncMessage($importDto);

        // GraphQL transformer output
        $transformer = $this->createMock(ProductTransformer::class);
        $transformer->method('transform')
            ->willReturn(['title' => 'Hoodie']); // GraphQL expects raw input object

        $client = $this->createMock(ProductClientInterface::class);
        $client->expects($this->once())
            ->method('createProduct')
            ->with(['title' => 'Hoodie'])
            ->willReturn(['id' => 'gid://shopify/Product/999']);

        $logger = $this->createMock(LoggerInterface::class);

        $handler = new ProductSyncMessageHandler(
            logger: $logger,
            shopify: $client,
            transformer: $transformer
        );

        $handler($message);

        $this->assertTrue(true);
    }
}
