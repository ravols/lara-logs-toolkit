<?php

namespace Ravols\LaraLogsToolkit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Ravols\LaraLogsToolkit\Data\AllChannelsLogAnalysisData;
use Ravols\LaraLogsToolkit\Data\LogAnalysisComparisonData;
use Ravols\LaraLogsToolkit\Data\LogCountsData;
use Ravols\LaraLogsToolkit\Tools\LogAnalyser;
use Ravols\LaraLogsToolkit\Tools\LogReader;

class LaraLogsToolkit
{
    private const DEFAULT_EXCLUDED_CHANNELS = ['single', 'daily', 'null', 'papertrail', 'stderr', 'syslog', 'errorlog', 'slack', 'emergency'];

    private ?array $excludedChannels = null;

    public function getLogAnalysis(LoggerInterface $logger): LogCountsData
    {
        $logAnalyser = new LogAnalyser();

        return $logAnalyser->setLogger($logger)->analyzeLogs();
    }

    public function getLogAnalysisComparison(LoggerInterface $logger): LogAnalysisComparisonData
    {
        try {
            $logFileName = collect($logger->getHandlers())->map(function ($handler): string {
                return str($handler->getUrl())->explode('/')->last() ?? 'unknown';
            })->flatten()->implode('_');
        } catch (\Exception $e) {
            $logFileName = 'unknown';
        }

        $cacheKey = 'lara_logs_toolkit_cached_count_of_records_in_log_file_' . $logFileName;
        $currentCounts = $this->getLogAnalysis($logger);

        $cachedCountsArray = Cache::get($cacheKey, [
            ...(new LogCountsData())->toArray(),
            'cachedAt' => null,
        ]);

        $cachedCounts = LogCountsData::fromArray($cachedCountsArray);

        $differences = new LogCountsData(
            error: $currentCounts->error - $cachedCounts->error,
            info: $currentCounts->info - $cachedCounts->info,
            warning: $currentCounts->warning - $cachedCounts->warning,
            emergency: $currentCounts->emergency - $cachedCounts->emergency,
            alert: $currentCounts->alert - $cachedCounts->alert,
            critical: $currentCounts->critical - $cachedCounts->critical,
            debug: $currentCounts->debug - $cachedCounts->debug,
            notice: $currentCounts->notice - $cachedCounts->notice,
        );

        Cache::put($cacheKey, [
            ...$currentCounts->toArray(),
            'cachedAt' => now()->toDateTimeString(),
        ], config('lara-logs-toolkit.comparison_cache_ttl', 600));

        return new LogAnalysisComparisonData(
            current: $currentCounts,
            cached: $cachedCounts,
            differences: $differences,
            logFileName: $logFileName,
            cachedAt: $cachedCountsArray['cachedAt'] ?? null,
        );
    }

    /**
     * Override the default excluded channels
     *
     * @param array<string>|string $channels Channel names to exclude
     */
    public function excludeChannels(array|string $channels): self
    {
        $this->excludedChannels = is_string($channels) ? [$channels] : $channels;

        return $this;
    }

    /**
     * Include channels by removing them from excluded channels
     *
     * @param array<string>|string $channels Channel names to include
     */
    public function includeChannels(array|string $channels): self
    {
        $channelsToInclude = is_string($channels) ? [$channels] : $channels;
        $currentExcluded = $this->excludedChannels ?? self::DEFAULT_EXCLUDED_CHANNELS;
        $this->excludedChannels = array_values(array_diff($currentExcluded, $channelsToInclude));

        return $this;
    }

    /**
     * Get log analysis for channels defined in config/logging.php
     *
     * @param bool $useCache If true, uses getLogAnalysisComparison instead of getLogAnalysis
     * @return AllChannelsLogAnalysisData
     */
    public function getAllChannelsLogAnalysis(bool $useCache = false): AllChannelsLogAnalysisData
    {
        $allChannels = array_keys(config('logging.channels', []));
        $excludedChannels = $this->excludedChannels ?? self::DEFAULT_EXCLUDED_CHANNELS;
        $channelsToAnalyze = array_diff($allChannels, $excludedChannels);

        $results = [];

        foreach ($channelsToAnalyze as $channelName) {
            try {
                $logger = Log::channel($channelName);
                if ($useCache) {
                    $results[$channelName] = $this->getLogAnalysisComparison($logger);
                } else {
                    $results[$channelName] = $this->getLogAnalysis($logger);
                }
            } catch (\Exception $e) {
                if ($useCache) {
                    $results[$channelName] = new LogAnalysisComparisonData(
                        current: new LogCountsData(),
                        cached: new LogCountsData(),
                        differences: new LogCountsData(),
                        logFileName: 'unknown',
                    );
                } else {
                    $results[$channelName] = new LogCountsData();
                }
            }
        }

        return new AllChannelsLogAnalysisData($results);
    }

    /**
     * Get the last error message from a specific channel
     *
     * @param string $channel Channel name (default: 'stack')
     * @param bool $withStackTrace If true, returns error with stack trace
     * @return string|null The last error message or null if no error found
     */
    public function getLastError(string $channel = 'stack', bool $withStackTrace = false): ?string
    {
        try {
            $logger = Log::channel($channel);
            $logReader = new LogReader();
            $content = $logReader->setLogger($logger)->getLogContent();

            if ($content === null) {
                return null;
            }

            return $logReader->extractLastLogEntry($content, 'ERROR', $withStackTrace);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the last log record from a specific channel (any log level)
     *
     * @param string $channel Channel name (default: 'stack')
     * @param bool $withStackTrace If true, returns record with stack trace
     * @return string|null The last log record or null if no record found
     */
    public function getLastRecord(string $channel = 'stack', bool $withStackTrace = false): ?string
    {
        try {
            $logger = Log::channel($channel);
            $logReader = new LogReader();
            $content = $logReader->setLogger($logger)->getLogContent();

            if ($content === null) {
                return null;
            }

            return $logReader->extractLastRecord($content, $withStackTrace);
        } catch (\Exception $e) {
            return null;
        }
    }
}
