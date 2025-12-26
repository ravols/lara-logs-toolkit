<?php

namespace Ravols\LaraLogsToolkit;

use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;
use Ravols\LaraLogsToolkit\Data\LogAnalysisComparisonData;
use Ravols\LaraLogsToolkit\Data\LogCountsData;
use Ravols\LaraLogsToolkit\Tools\LogAnalyser;

class LaraLogsToolkit
{
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
            'error' => 0,
            'info' => 0,
            'warning' => 0,
            'emergency' => 0,
            'alert' => 0,
            'critical' => 0,
            'debug' => 0,
            'notice' => 0,
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

        $cacheTtl = config('lara-logs-toolkit.comparison_cache_ttl', 600);
        Cache::put($cacheKey, array_merge($currentCounts->toArray(), ['cachedAt' => now()->toDateTimeString()]), $cacheTtl);

        return new LogAnalysisComparisonData(
            current: $currentCounts,
            cached: $cachedCounts,
            differences: $differences,
            logFileName: $logFileName,
            cachedAt: $cachedCountsArray['cachedAt'] ?? null,
        );
    }
}
