<?php

namespace App\Command\Product;

use App\Product\Client\ProductRestClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'shopify:test',
    description: 'Tests Shopify Product API connectivity'
)]
class TestShopifyProductCommand extends Command
{
    public function __construct(
        private ProductRestClient $shopify
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("Testing Shopify API…");

        $result = $this->shopify->post('/products.json', [
            'product' => [
                'title' => 'Test Product From CLI',
                'body_html' => 'A product created by Symfony test command',
            ]
        ]);

        $output->writeln(json_encode($result, JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
