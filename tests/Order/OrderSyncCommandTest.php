<?php

namespace App\Tests\Order;

use App\Command\Order\OrderSyncCommand;
use App\Order\Service\OrderSyncService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class OrderSyncCommandTest extends TestCase
{
    public function testSyncOrdersSuccess()
    {
        // Mock OrderSyncService
        $serviceMock = $this->createMock(OrderSyncService::class);

        // Expect syncWindow() to be called once with min=1, max=72
        $serviceMock->expects($this->once())
            ->method('syncWindow')
            ->with(1, 72)
            ->willReturn(5);   // pretend 5 orders synced

        // Mock logger (not required to assert anything)
        $loggerMock = $this->createMock(LoggerInterface::class);

        // Instantiate the command
        $command = new OrderSyncCommand($serviceMock, $loggerMock);

        // Create input + output buffers
        $input  = new ArrayInput([]);
        $output = new BufferedOutput();

        // Run the command
        $exitCode = $command->run($input, $output);

        // Assert exit code
        $this->assertEquals(0, $exitCode);

        // Read command output
        $text = $output->fetch();

        // Assertions about CLI text
        $this->assertStringContainsString("🚀 Starting Shopify Order Sync (1-72 hours)…", $text);
        $this->assertStringContainsString("🎉 Sync complete — 5 orders queued.", $text);
    }

    public function testSyncOrdersWithCustomHours()
    {
        $serviceMock = $this->createMock(OrderSyncService::class);

        // Expect syncWindow() to be called with the custom values
        $serviceMock->expects($this->once())
            ->method('syncWindow')
            ->with(3, 24)
            ->willReturn(8);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $command = new OrderSyncCommand($serviceMock, $loggerMock);

        $input = new ArrayInput([
            '--min-hours' => 3,
            '--max-hours' => 24
        ]);
        $output = new BufferedOutput();

        $exit = $command->run($input, $output);

        $this->assertSame(0, $exit);

        $out = $output->fetch();

        $this->assertStringContainsString("🚀 Starting Shopify Order Sync (3-24 hours)…", $out);
        $this->assertStringContainsString("🎉 Sync complete — 8 orders queued.", $out);
    }
}
