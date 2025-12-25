# Lara Logs Toolkit

A Laravel package that helps you track deployments and monitor log file growth by providing deployment markers and log record counting capabilities.

## Problems This Package Solves

### Problem 1: When Did Errors Occur?

**The Issue:** When you see errors in your logs, it's often impossible to tell if they happened before or after your latest deployment. This makes debugging much harder, especially when you need to determine if a deployment introduced new issues.

**The Solution:** This package automatically logs a timestamp marker whenever `composer dump-autoload` finishes. By adding a single line to your `composer.json`, you'll have clear deployment markers in your logs, making it easy to see which errors occurred before or after each deployment.

### Problem 2: Log Files Filling Up After Deployment

**The Issue:** After a deployment, your logs might start filling up with errors, but if you're distracted or not actively monitoring, you might miss critical issues until they become severe.

**The Solution:** This package provides a command to quickly check how many log records exist in any specified log channel from your `config/logging.php`. Run it after deployments or set it up in monitoring to get instant visibility into log growth.

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

This will create `config/lara-logs-toolkit.php` where you can configure the default log channel:

```php
'channel' => env('LARA_LOGS_TOOLKIT_CHANNEL', 'daily'),
```

You can also set the channel via `.env`:

```env
LARA_LOGS_TOOLKIT_CHANNEL=daily
```

## Usage

### Deployment Tracking

To automatically log when `composer dump-autoload` finishes, add this line to the `scripts` section of your `composer.json`:

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

## Available Commands

- `php artisan lara-logs:composer-dump-autoload` - Manually log a deployment marker
- `php artisan lara-logs:check-records [channel]` - Check log record count for a channel

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jaroslav Stefanec](https://github.com/jaroslavstefanec)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
