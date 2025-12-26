<?php

namespace Ravols\LaraLogsToolkit\Data;

class LogAnalysisComparisonData
{
    public function __construct(
        public readonly LogCountsData $current,
        public readonly LogCountsData $cached,
        public readonly LogCountsData $differences,
        public readonly string $logFileName,
        public readonly ?string $cachedAt = null,
    ) {
    }

    public function isNewError(): bool
    {
        return $this->differences->error > 0;
    }

    public function isNewWarning(): bool
    {
        return $this->differences->warning > 0;
    }

    public function isNewCritical(): bool
    {
        return $this->differences->critical > 0;
    }

    public function isNewAlert(): bool
    {
        return $this->differences->alert > 0;
    }

    public function isNewEmergency(): bool
    {
        return $this->differences->emergency > 0;
    }

    public function hasAnyNewIssues(): bool
    {
        return $this->isNewError()
            || $this->isNewWarning()
            || $this->isNewCritical()
            || $this->isNewAlert()
            || $this->isNewEmergency();
    }

    public function getNewErrorCount(): int
    {
        return max(0, $this->differences->error);
    }

    public function getNewWarningCount(): int
    {
        return max(0, $this->differences->warning);
    }

    public function getNewCriticalCount(): int
    {
        return max(0, $this->differences->critical);
    }

    public function getNewAlertCount(): int
    {
        return max(0, $this->differences->alert);
    }

    public function getNewEmergencyCount(): int
    {
        return max(0, $this->differences->emergency);
    }

    public function getTotalNewIssuesCount(): int
    {
        return $this->getNewErrorCount()
            + $this->getNewWarningCount()
            + $this->getNewCriticalCount()
            + $this->getNewAlertCount()
            + $this->getNewEmergencyCount();
    }

    public function getNewInfoCount(): int
    {
        return max(0, $this->differences->info);
    }

    public function getNewDebugCount(): int
    {
        return max(0, $this->differences->debug);
    }

    public function getNewNoticeCount(): int
    {
        return max(0, $this->differences->notice);
    }

    public function getTotalNewCount(): int
    {
        return max(0, $this->differences->error)
            + max(0, $this->differences->info)
            + max(0, $this->differences->warning)
            + max(0, $this->differences->emergency)
            + max(0, $this->differences->alert)
            + max(0, $this->differences->critical)
            + max(0, $this->differences->debug)
            + max(0, $this->differences->notice);
    }

    public function getCurrentErrorCount(): int
    {
        return $this->current->error;
    }

    public function getCurrentWarningCount(): int
    {
        return $this->current->warning;
    }

    public function getCachedErrorCount(): int
    {
        return $this->cached->error;
    }

    public function getCachedWarningCount(): int
    {
        return $this->cached->warning;
    }
}
