# Lara Logs Toolkit

A Laravel package that helps you track deployments and monitor log file growth by providing deployment markers and log record counting capabilities.

## Problems This Package Solves

### Problem 1: Log Files Filling Up After Deployment

**The Issue:** After a deployment, your logs might start filling up with errors, but if you're distracted or not actively monitoring, you might miss critical issues until they become severe.

**The Solution:** This package provides a command to quickly check how many log records exist in any specified log channel from your `config/logging.php`. Run it after deployments or set it up in monitoring to get instant visibility into log growth.

**Extended Possibilities:** You can create your own custom commands that check for new log records and send notifications (Slack, email, or SMS) when a threshold is met. This allows you to set up automated alerting that triggers when log growth exceeds acceptable levels after deployments.

### Problem 2: When Did Errors Occur?

**The Issue:** When you see errors in your logs, it's often impossible to tell if they happened before or after your latest deployment. This makes debugging much harder, especially when you need to determine if a deployment introduced new issues.

**The Solution:** This package automatically logs a timestamp marker whenever `composer dump-autoload` finishes. By adding a single line to your `composer.json`, you'll have clear deployment markers in your logs, making it easy to see which errors occurred before or after each deployment.

## Installation

You can install the package via composer:

```bash
composer require ravols/lara-logs-toolkit
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=lara-logs-toolkit-config
```

This will create `config/lara-logs-toolkit.php` where you can configure the default log channel and cache settings:

```php
'composer_dump_autoload_channel' => env('LARA_LOGS_TOOLKIT_COMPOSER_DUMP_AUTOLOAD_CHANNEL', 'daily'),
'comparison_cache_ttl' => env('LARA_LOGS_TOOLKIT_COMPARISON_CACHE_TTL', 600),
```

**Configuration Options:**
- `composer_dump_autoload_channel` - The log channel where the composer dump-autoload notification will be written. This should match one of the channels defined in your application's logging configuration.
- `comparison_cache_ttl` - Cache time-to-live in seconds for storing log analysis comparison results (default: `600` seconds / 10 minutes)

## Usage

### Deployment Tracking

To automatically log when `composer dump-autoload` finishes, you need to add the command to your `composer.json` file.

**Step 1:** Open your `composer.json` file in the root of your Laravel project.

**Step 2:** Find the `scripts` section. If it doesn't exist, create it at the root level of your JSON.

**Step 3:** Add or update the `post-autoload-dump` array. Add `"@php artisan lara-logs:composer-dump-autoload"` as the last item in the array.

**Example - of a `post-autoload-dump` script:**

```json
{
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi",
            "@php artisan lara-logs:composer-dump-autoload"
        ]
    }
}
```

**Important:** The line `"@php artisan lara-logs:composer-dump-autoload"` should be added as the **last item** in the `post-autoload-dump` array, after the existing Laravel commands.

Now, every time you run `composer dump-autoload` (which happens automatically during deployments), you'll see a log entry like:

```
[2025-12-25 11:01:23] local.INFO: Composer dump-autoload finished at 2025-12-25 11:01:23
```

This creates a clear marker in your logs, making it easy to identify which errors occurred before or after each deployment.

### Checking Log Record Counts

To check how many log records exist in a specific channel:

```bash
php artisan lara-logs:check-records [channel]
```

If no channel is specified, it will use the channel configured in `config/lara-logs-toolkit.php` (defaults to `daily`).

**Examples:**

```bash
# Check the default channel (from config)
php artisan lara-logs:check-records

# Check a specific channel
php artisan lara-logs:check-records daily
php artisan lara-logs:check-records api
```

The command will output:

```
Channel 'daily' contains 1523 log record(s).
```

This is especially useful after deployments to quickly see if error counts have increased, or you can integrate it into your monitoring/alerting system.

### Comparing Log Analysis with Cached Results

The package provides a method to compare current log analysis with previously cached results. This is useful for detecting changes in log counts over time:

```php
use Illuminate\Support\Facades\Log;
use Ravols\LaraLogsToolkit\Facades\LaraLogsToolkit;

$logger = Log::channel('daily');
$comparison = LaraLogsToolkit::getLogAnalysisComparison($logger);

// Access the results using DTO properties
$currentCounts = $comparison->current;      // LogCountsData DTO
$cachedCounts = $comparison->cached;        // LogCountsData DTO
$differences = $comparison->differences;    // LogCountsData DTO

// Example: Check if errors increased using DTO methods
if ($comparison->isNewError()) {
    echo "Errors increased by {$comparison->getNewErrorCount()} since last check\n";
}

// Or access directly via properties
if ($comparison->differences->error > 0) {
    echo "Errors increased by {$comparison->differences->error} since last check\n";
}

// Use helper methods for convenience
if ($comparison->hasAnyNewIssues()) {
    echo "Total new issues: {$comparison->getTotalNewIssuesCount()}\n";
    echo "New errors: {$comparison->getNewErrorCount()}\n";
    echo "New warnings: {$comparison->getNewWarningCount()}\n";
}

// Check when the cache was created
if ($comparison->cachedAt !== null) {
    echo "Cache was created at: {$comparison->cachedAt}\n";
} else {
    echo "No previous cache found (first run)\n";
}
```

The method automatically caches the current analysis results for comparison on the next call. The cache TTL is configurable via `config/lara-logs-toolkit.php` using the `comparison_cache_ttl` key (default: 600 seconds).

**Return Type:** `LogAnalysisComparisonData` DTO containing:
- `current` - `LogCountsData` DTO with current log counts by level
- `cached` - `LogCountsData` DTO with previously cached log counts
- `differences` - `LogCountsData` DTO showing the difference between current and cached (current - cached)
- `logFileName` - The log file name used for caching
- `cachedAt` - DateTime string (e.g., "2025-01-15 10:30:45") indicating when the cache was created, or `null` if no cache existed

**LogCountsData DTO Properties:**
- `error`, `info`, `warning`, `emergency`, `alert`, `critical`, `debug`, `notice` - Individual log level counts
- `getError()`, `getInfo()`, `getWarning()`, etc. - Getter methods for each log level
- `getTotal()` - Get total count across all levels

### Creating Custom Alert Commands

You can create your own commands that monitor log growth and send notifications when thresholds are exceeded. Here's an example:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Ravols\LaraLogsToolkit\LaraLogsToolkit;

class MonitorLogGrowth extends Command
{
    protected $signature = 'logs:monitor {channel=daily : The log channel to monitor} {--threshold=100 : Alert threshold}';
    protected $description = 'Monitor log growth and send alerts when threshold is exceeded';

    public function handle(LaraLogsToolkit $laraLogsToolkit): int
    {
        $channel = $this->argument('channel');
        $threshold = (int) $this->option('threshold');
        
        $logger = Log::channel($channel);
        
        // Get total count using DTO
        $logCounts = $laraLogsToolkit->getLogAnalysis($logger);
        $count = $logCounts->getTotal();
        
        // Or get count for a specific log level
        // $count = $logCounts->getError(); 
        
        // Or compare with cached results to detect changes
        // $comparison = $laraLogsToolkit->getLogAnalysisComparison($logger);
        // if ($comparison->isNewError()) {
        //     $newErrors = $comparison->getNewErrorCount();
        //     // Handle new errors since last check
        // }
        
        if ($count > $threshold) {
            // Send Slack notification
            Notification::route('slack', config('services.slack.webhook_url'))
                ->notify(new LogThresholdExceeded($channel, $count, $threshold));
            
            // Or send email
            // Mail::to(config('app.admin_email'))->send(new LogAlert($channel, $count));
            
            // Or send SMS
            // Notification::route('vonage', config('app.admin_phone'))
            //     ->notify(new LogThresholdExceeded($channel, $count, $threshold));
            
            $this->warn("Alert sent! Channel '{$channel}' has {$count} records (threshold: {$threshold})");
            return self::FAILURE;
        }
        
        $this->info("Channel '{$channel}' is within limits ({$count} records)");
        return self::SUCCESS;
    }
}
```

You can then schedule this command in your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Check every 5 minutes after deployment
    $schedule->command('logs:monitor daily --threshold=100')->everyFiveMinutes();
}
```

## Data Transfer Objects (DTOs)

The package uses DTOs to provide type-safe access to log analysis data:

### `LogCountsData`

Represents log counts for all log levels. Provides both property access and getter methods.

**Methods:**
- `getError()`, `getInfo()`, `getWarning()`, etc. - Getter methods for each log level
- `getTotal()` - Get total count across all levels
- `toArray()` - Convert to array format
- `fromArray(array $counts): self` - Static factory method to create from array

**Example:**
```php
$logCounts = LaraLogsToolkit::getLogAnalysis(Log::channel('daily'));

// Property access
$errors = $logCounts->error;

// Getter methods
$errors = $logCounts->getError();

// Get total
$total = $logCounts->getTotal();
```

### `LogAnalysisComparisonData`

Represents a comparison between current and cached log analysis results.

**Properties:**
- `current` - `LogCountsData` DTO with current log counts
- `cached` - `LogCountsData` DTO with previously cached log counts
- `differences` - `LogCountsData` DTO showing differences (current - cached)
- `logFileName` - The log file name used for caching
- `cachedAt` - DateTime string (e.g., "2025-01-15 10:30:45") indicating when the cache was created, or `null` if no cache existed

**Helper Methods:**
- `isNewError()`, `isNewWarning()`, `isNewCritical()`, `isNewAlert()`, `isNewEmergency()` - Boolean checks for new entries
- `hasAnyNewIssues()` - Check if there are any new issues
- `getNewErrorCount()`, `getNewWarningCount()`, etc. - Get count of new entries (returns 0 if negative)
- `getTotalNewIssuesCount()` - Get total count of all new issues
- `getTotalNewCount()` - Get total count of all new log entries
- `getCurrentErrorCount()`, `getCurrentWarningCount()` - Get current counts
- `getCachedErrorCount()`, `getCachedWarningCount()` - Get cached counts

**Example:**
```php
$comparison = LaraLogsToolkit::getLogAnalysisComparison(Log::channel('daily'));

// Check for new errors
if ($comparison->isNewError()) {
    $newErrors = $comparison->getNewErrorCount();
}

// Access DTO properties directly
$currentErrors = $comparison->current->error;
$cachedErrors = $comparison->cached->error;
$errorDiff = $comparison->differences->error;

// Check when cache was created
if ($comparison->cachedAt) {
    echo "Last cached at: {$comparison->cachedAt}\n";
}
```

## API Reference

### Available Methods

The `LaraLogsToolkit` class provides the following methods:

#### `getLogAnalysis(LoggerInterface $logger): LogCountsData`

Analyzes logs and returns a `LogCountsData` DTO with detailed log counts by level.

```php
$logCounts = LaraLogsToolkit::getLogAnalysis(Log::channel('daily'));

// Get counts of errors and information
$errorCount = $logCounts->getError();
$infoCount = $logCounts->getInfo();

// Get total count
$total = $logCounts->getTotal();
```

#### `getLogAnalysisComparison(LoggerInterface $logger): LogAnalysisComparisonData`

Compares current log analysis with cached results and returns a `LogAnalysisComparisonData` DTO containing:
- `current` - `LogCountsData` DTO with current log counts by level
- `cached` - `LogCountsData` DTO with previously cached log counts
- `differences` - `LogCountsData` DTO showing differences between current and cached (current - cached)
- `logFileName` - The log file name used for caching
- `cachedAt` - DateTime string indicating when the cache was created, or `null` if no cache existed

The results are automatically cached for the next comparison. Cache TTL is configurable.

```php
$comparison = LaraLogsToolkit::getLogAnalysisComparison(Log::channel('daily'));

// Use helper methods
if ($comparison->isNewError()) {
    $newErrors = $comparison->getNewErrorCount();
}


// Check for any new issues
if ($comparison->hasAnyNewIssues()) {
    $totalNewIssues = $comparison->getTotalNewIssuesCount();
}

// Check when the cache was created
if ($comparison->cachedAt) {
    echo "Cache created at: {$comparison->cachedAt}\n";
}
```

**LogAnalysisComparisonData Helper Methods:**
- `isNewError()`, `isNewWarning()`, `isNewCritical()`, `isNewAlert()`, `isNewEmergency()` - Check if specific log level has new entries
- `hasAnyNewIssues()` - Check if there are any new issues (errors, warnings, critical, alert, or emergency)
- `getNewErrorCount()`, `getNewWarningCount()`, etc. - Get count of new entries for each level (returns 0 if negative)
- `getTotalNewIssuesCount()` - Get total count of all new issues
- `getTotalNewCount()` - Get total count of all new log entries across all levels
- `getCurrentErrorCount()`, `getCurrentWarningCount()` - Get current counts
- `getCachedErrorCount()`, `getCachedWarningCount()` - Get cached counts

## Available Commands

- `php artisan lara-logs:composer-dump-autoload` - Manually log a deployment marker
- `php artisan lara-logs:check-records [channel]` - Check log record count for a channel

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
