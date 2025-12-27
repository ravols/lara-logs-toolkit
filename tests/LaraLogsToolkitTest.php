<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Ravols\LaraLogsToolkit\Enums\DeleteLogAction;
use Ravols\LaraLogsToolkit\LaraLogsToolkit;

beforeEach(function (): void {
    Cache::get('lara_logs_toolkit_cached_count_of_records_in_log_file_single', []);
    $this->channelName = 'single';
    $this->toolkit = new LaraLogsToolkit();
    $this->logger = Log::channel($this->channelName);

    Cache::flush();

    Artisan::call('lara-logs:delete-logs', [
        '--channels' => [$this->channelName],
        '--action' => DeleteLogAction::ALL->value,
    ]);
});

afterEach(function (): void {
    Artisan::call('lara-logs:delete-logs', [
        '--channels' => [$this->channelName],
        '--action' => 'all',
    ]);
});

it('can delete all logs and analyze empty log file', function (): void {
    Artisan::call('lara-logs:delete-logs', [
        '--channels' => [$this->channelName],
        '--action' => 'all',
    ]);

    $analysis = $this->toolkit->getLogAnalysis($this->logger);

    expect($analysis->getTotal())->toBe(0)
        ->and($analysis->error)->toBe(0)
        ->and($analysis->info)->toBe(0)
        ->and($analysis->warning)->toBe(0);
});

it('can analyze log records after creating them', function (): void {
    Artisan::call('lara-logs:delete-logs', [
        '--channels' => [$this->channelName],
        '--action' => 'all',
    ]);

    Log::channel($this->channelName)->info('Test info message');
    Log::channel($this->channelName)->error('Test error message');
    Log::channel($this->channelName)->warning('Test warning message');

    $analysis = $this->toolkit->getLogAnalysis($this->logger);

    expect($analysis->getTotal())->toBe(3)
        ->and($analysis->info)->toBe(1)
        ->and($analysis->error)->toBe(1)
        ->and($analysis->warning)->toBe(1);
});

it('can compare log analysis and detect new records', function (): void {
    Artisan::call('lara-logs:delete-logs', [
        '--channels' => [$this->channelName],
        '--action' => 'all',
    ]);

    Log::channel($this->channelName)->info('Initial info message');
    Log::channel($this->channelName)->error('Initial error message');

    $firstComparison = $this->toolkit->getLogAnalysisComparison($this->logger);

    expect($firstComparison->current->getTotal())->toBe(2)
        ->and($firstComparison->cached->getTotal())->toBe(0)
        ->and($firstComparison->differences->getTotal())->toBe(2);

    Log::channel($this->channelName)->info('New info message');
    Log::channel($this->channelName)->warning('New warning message');

    $secondComparison = $this->toolkit->getLogAnalysisComparison($this->logger);

    expect($secondComparison->current->getTotal())->toBe(4)
        ->and($secondComparison->cached->getTotal())->toBe(2)
        ->and($secondComparison->differences->getTotal())->toBe(2)
        ->and($secondComparison->differences->info)->toBe(1)
        ->and($secondComparison->differences->warning)->toBe(1);
});

it('can handle all log levels', function (): void {
    Artisan::call('lara-logs:delete-logs', [
        '--channels' => [$this->channelName],
        '--action' => 'all',
    ]);

    Log::channel($this->channelName)->emergency('Emergency message');
    Log::channel($this->channelName)->alert('Alert message');
    Log::channel($this->channelName)->critical('Critical message');
    Log::channel($this->channelName)->error('Error message');
    Log::channel($this->channelName)->warning('Warning message');
    Log::channel($this->channelName)->notice('Notice message');
    Log::channel($this->channelName)->info('Info message');
    Log::channel($this->channelName)->debug('Debug message');

    $analysis = $this->toolkit->getLogAnalysis($this->logger);

    expect($analysis->getTotal())->toBe(8)
        ->and($analysis->emergency)->toBe(1)
        ->and($analysis->alert)->toBe(1)
        ->and($analysis->critical)->toBe(1)
        ->and($analysis->error)->toBe(1)
        ->and($analysis->warning)->toBe(1)
        ->and($analysis->notice)->toBe(1)
        ->and($analysis->info)->toBe(1)
        ->and($analysis->debug)->toBe(1);
});
