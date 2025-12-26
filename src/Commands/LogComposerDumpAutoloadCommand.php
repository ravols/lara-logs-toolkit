<?php

namespace Ravols\LaraLogsToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LogComposerDumpAutoloadCommand extends Command
{
    protected $signature = 'lara-logs:composer-dump-autoload';
    protected $description = 'Log a message indicating composer dump-autoload has finished';

    public function handle(): int
    {
        $channel = config('lara-logs-toolkit.composer_dump_autoload_channel', 'daily');
        $timestamp = now()->toDateTimeString();

        Log::channel($channel)->info("Composer dump-autoload finished at {$timestamp}");

        $this->info("Logged composer dump-autoload completion to '{$channel}' channel.");

        return self::SUCCESS;
    }
}
