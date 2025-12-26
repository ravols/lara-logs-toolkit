<?php

namespace Ravols\LaraLogsToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Ravols\LaraLogsToolkit\LaraLogsToolkit;

class CheckLogRecordsCommand extends Command
{
    protected $signature = 'lara-logs:check-records {channel? : The log channel to check (defaults to daily channel)}';
    protected $description = 'Check how many log records exist in a specified log channel';

    public function handle(LaraLogsToolkit $toolkit): int
    {
        $channelName = $this->argument('channel') ?? 'daily';

        if (! config("logging.channels.{$channelName}")) {
            $this->error("Channel '{$channelName}' not found in logging configuration.");

            return self::FAILURE;
        }

        $logger = Log::channel($channelName);
        $logCounts = $toolkit->getLogAnalysis($logger);
        $count = $logCounts->getTotal();

        $this->info("Channel '{$channelName}' contains {$count} log record(s).");

        return self::SUCCESS;
    }
}
