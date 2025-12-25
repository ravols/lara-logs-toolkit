<?php

namespace Slovar\LaraLogsToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Slovar\LaraLogsToolkit\LaraLogsToolkit;

class CheckLogRecordsCommand extends Command
{
    protected $signature = 'lara-logs:check-records {channel? : The log channel to check (defaults to configured channel)}';

    protected $description = 'Check how many log records exist in a specified log channel';

    public function handle(LaraLogsToolkit $toolkit): int
    {
        $channelName = $this->argument('channel') ?? config('lara-logs-toolkit.channel', 'daily');

        if (! config("logging.channels.{$channelName}")) {
            $this->error("Channel '{$channelName}' not found in logging configuration.");

            return self::FAILURE;
        }

        $logger = Log::channel($channelName);
        $count = $toolkit->countLogRecords($logger);

        if ($count === null) {
            $this->error("Could not determine log record count for channel '{$channelName}'.");

            return self::FAILURE;
        }

        $this->info("Channel '{$channelName}' contains {$count} log record(s).");

        return self::SUCCESS;
    }
}
