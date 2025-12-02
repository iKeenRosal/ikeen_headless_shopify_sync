<?php

namespace App\Command\Order;

use App\Order\Service\OrderSyncService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sync:orders',
    description: 'Sync Shopify orders into the system'
)]
class OrderSyncCommand extends Command
{
    public function __construct(
        private OrderSyncService $syncService,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('min-hours', null, InputOption::VALUE_REQUIRED, 'Minimum age in hours', 1)
            ->addOption('max-hours', null, InputOption::VALUE_REQUIRED, 'Maximum age in hours', 72);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $minHours = (int)$input->getOption('min-hours');
        $maxHours = (int)$input->getOption('max-hours');

        $output->writeln("🚀 Starting Shopify Order Sync ({$minHours}-{$maxHours} hours)…");

        // --- Simulated Pull Size (just for progress bar UI)
        // The real count is returned after sync.
        $progress = new ProgressBar($output, 50);
        $progress->start();

        $countSynced = $this->syncService->syncWindow($minHours, $maxHours);

        // Finish progress bar
        for ($i = 0; $i < 50; $i++) {
            usleep(20000);
            $progress->advance();
        }
        $progress->finish();
        $output->writeln("");

        $output->writeln("🎉 Sync complete — {$countSynced} orders queued.");

        return Command::SUCCESS;
    }
}
