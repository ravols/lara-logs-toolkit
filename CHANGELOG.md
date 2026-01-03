# Changelog

All notable changes to `ravols/lara-logs-toolkit` will be documented in this file.

## [1.1.0] - 2026-01-03

### Added
- **Last Error Retrieval**: Method to get the last error from a specific log channel
  - `getLastError(string $channel = 'stack', bool $withStackTrace = false)` - Returns the last error message from a channel
  - Supports optional stack trace inclusion
  - Default channel is 'stack'
  
- **Last Record Retrieval**: Method to get the last log record (any level) from a specific channel
  - `getLastRecord(string $channel = 'stack', bool $withStackTrace = false)` - Returns the last log entry from a channel
  - Supports optional stack trace inclusion
  - Works with all log levels (ERROR, INFO, WARNING, etc.)

### Changed
- **Code Refactoring**: Extracted log parsing logic into `LogReader` tool class for better maintainability
  - Created `LogReader` class in `Tools` namespace
  - Simplified log parsing methods using Laravel's `Str` facade
  - Improved code readability and consistency

## [1.0.1] - 2025-12-27

### Added
- **Log Deletion**: Command and tool to delete log records from configured log channels
  - `lara-logs:delete-logs` command to delete log records
  - Supports deletion of latest record or all logs
  - Interactive channel selection with multisearch
  - Production environment confirmation prompt
  - Supports both single and daily log file formats

### Features
- Delete latest log record or all logs from selected channels
- Flexible channel selection via command options or interactive prompts

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
