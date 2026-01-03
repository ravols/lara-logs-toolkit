<?php

namespace Ravols\LaraLogsToolkit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Ravols\LaraLogsToolkit\Data\AllChannelsLogAnalysisData;
use Ravols\LaraLogsToolkit\Data\LogAnalysisComparisonData;
use Ravols\LaraLogsToolkit\Data\LogCountsData;
use Ravols\LaraLogsToolkit\Tools\LogAnalyser;

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
     * @return AllChannelsLogAnalysisData
     */
    public function getAllChannelsLogAnalysis(): AllChannelsLogAnalysisData
    {
        $allChannels = array_keys(config('logging.channels', []));
        $excludedChannels = $this->excludedChannels ?? self::DEFAULT_EXCLUDED_CHANNELS;
        $channelsToAnalyze = array_diff($allChannels, $excludedChannels);

        $results = [];

        foreach ($channelsToAnalyze as $channelName) {
            try {
                $logger = Log::channel($channelName);
                $results[$channelName] = $this->getLogAnalysis($logger);
            } catch (\Exception $e) {
                $results[$channelName] = new LogCountsData();
            }
        }

        return new AllChannelsLogAnalysisData($results);
    }
}
