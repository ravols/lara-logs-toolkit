<?php

use Illuminate\Support\Facades\Log;
use Ravols\LaraLogsToolkit\Enums\DeleteLogAction;
use Ravols\LaraLogsToolkit\Tools\LogAnalyser;
use Ravols\LaraLogsToolkit\Tools\LogModifier;

beforeEach(function (): void {
    $this->channelName = 'single';
    $this->logger = Log::channel($this->channelName);
    $this->logModifier = new LogModifier();
    $this->logModifier->setLogger($this->logger);
});

it('can delete all logs after adding log entries', function (): void {
    // Add some log entries
    $testMessage = 'Test log message for deletion';
    Log::channel($this->channelName)->info($testMessage);
    Log::channel($this->channelName)->error('Another test error message');
    Log::channel($this->channelName)->warning('Test warning message');

    // Verify logs exist by checking the log file
    $logAnalyser = new LogAnalyser();
    $logFile = $logAnalyser->setLogger($this->logger)->getLogFilePath();

    expect($logFile)->not->toBeNull()
        ->and(file_exists($logFile))->toBeTrue();

    $logContent = file_get_contents($logFile);
    expect($logContent)->toContain($testMessage);

    // Delete all logs
    $this->logModifier->delete(DeleteLogAction::ALL);

    // Verify the log file is deleted
    expect(file_exists($logFile))->toBeFalse();
});

it('can delete latest record after adding log entries', function (): void {
    // Add some log entries
    $testMessage = 'Test log message for latest deletion';
    Log::channel($this->channelName)->info($testMessage);

    // Verify log exists
    $logAnalyser = new LogAnalyser();
    $logFile = $logAnalyser->setLogger($this->logger)->getLogFilePath();

    expect($logFile)->not->toBeNull()
        ->and(file_exists($logFile))->toBeTrue();

    $logContent = file_get_contents($logFile);
    expect($logContent)->toContain($testMessage);

    // Delete latest record (which deletes the entire file based on current implementation)
    $this->logModifier->delete(DeleteLogAction::LATEST);

    // Verify the log file is deleted
    expect(file_exists($logFile))->toBeFalse();
});
