# Changelog

All notable changes to `ravols/lara-logs-toolkit` will be documented in this file.

## [1.0.0] - 2025-12-25

### Added
- **Deployment Tracking**: Automatic logging of `composer dump-autoload` completion timestamps to help identify when errors occurred relative to deployments
  - `lara-logs:composer-dump-autoload` command to manually log deployment markers
  - Integration with composer scripts for automatic deployment tracking
  
- **Log Record Counting**: Command to check total log record counts in any configured log channel
  - `lara-logs:check-records [channel]` command to count log records
  - Supports all log drivers (single, daily, stack)
  - Uses existing `LogAnalyser` functionality for consistent log analysis

- **Configuration**: Publishable configuration file (`config/lara-logs-toolkit.php`)
  - Configurable log channel for deployment markers (defaults to `daily`)
  - Environment variable support via `LARA_LOGS_TOOLKIT_CHANNEL`

- **Service Provider**: Laravel auto-discovery support
  - Automatic service provider registration
  - Facade alias: `LaraLogsToolkit`

### Features
- Helps identify if errors occurred before or after deployment
- Monitor log file growth after deployments
- Quick visibility into log record counts across different channels
