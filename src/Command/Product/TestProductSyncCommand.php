<?php

namespace App\Command\Product;

use App\Product\Dto\ProductImportDto;
use App\Product\Dto\ProductVariantDto;
use App\Product\Product\Service\ProductSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'test:shopify-sync',
    description: 'Tests product sync into Shopify'
)]
class TestProductSyncCommand extends Command
{
    public function __construct(private ProductSyncService $syncer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $product = new ProductImportDto(
            externalId: 'ABC123',
            title: 'Sample Hoodie',
            description: 'A comfy hoodie.',
            brand: 'KeenRosalApparel',
            category: 'Apparel',
            variants: [
                new ProductVariantDto(
                    sku: 'HD-RED-S',
                    color: 'Red',
                    size: 'S',
                    price: 39.99,
                    currency: 'USD',
                    inventory: 25
                )
            ],
            imageUrls: [
                'https://cdn.shopify.com/s/files/1/0000/0001/products/hoodie.jpg'
            ]
        );

        $response = $this->syncer->sync($product);

        dump($response);

        return Command::SUCCESS;
    }
}
