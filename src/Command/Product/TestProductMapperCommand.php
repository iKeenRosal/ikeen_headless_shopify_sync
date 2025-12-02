<?php

namespace App\Command\Product;

use App\Product\Mapper\ProductMapper;
use App\Product\Message\ProductSyncMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'test:mapper',
    description: 'Add a short description for your command',
)]
class TestProductMapperCommand extends Command
{
    public function __construct(private MessageBusInterface $bus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mapper = new ProductMapper();
    
        $dto = $mapper->map([
            'externalId' => 'ABC123',
            'title' => 'Red Hoodie',
            'variants' => [
                [
                    'sku' => 'RH-RED-S',
                    'color' => 'Red',
                    'size' => 'S',
                    'price' => 29.99
                ]
            ]
        ]);
    
        $this->bus->dispatch(new ProductSyncMessage($dto));
    
        $output->writeln("Message dispatched to queue!");
    
        return Command::SUCCESS;
    }
}
