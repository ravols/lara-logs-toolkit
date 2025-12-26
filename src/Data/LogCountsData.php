<?php

namespace Ravols\LaraLogsToolkit\Data;

class LogCountsData
{
    public function __construct(
        public readonly int $error = 0,
        public readonly int $info = 0,
        public readonly int $warning = 0,
        public readonly int $emergency = 0,
        public readonly int $alert = 0,
        public readonly int $critical = 0,
        public readonly int $debug = 0,
        public readonly int $notice = 0,
    ) {
    }

    public static function fromArray(array $counts): self
    {
        return new self(
            error: $counts['error'] ?? 0,
            info: $counts['info'] ?? 0,
            warning: $counts['warning'] ?? 0,
            emergency: $counts['emergency'] ?? 0,
            alert: $counts['alert'] ?? 0,
            critical: $counts['critical'] ?? 0,
            debug: $counts['debug'] ?? 0,
            notice: $counts['notice'] ?? 0,
        );
    }

    public function toArray(): array
    {
        return [
            'error' => $this->error,
            'info' => $this->info,
            'warning' => $this->warning,
            'emergency' => $this->emergency,
            'alert' => $this->alert,
            'critical' => $this->critical,
            'debug' => $this->debug,
            'notice' => $this->notice,
        ];
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getInfo(): int
    {
        return $this->info;
    }

    public function getWarning(): int
    {
        return $this->warning;
    }

    public function getEmergency(): int
    {
        return $this->emergency;
    }

    public function getAlert(): int
    {
        return $this->alert;
    }

    public function getCritical(): int
    {
        return $this->critical;
    }

    public function getDebug(): int
    {
        return $this->debug;
    }

    public function getNotice(): int
    {
        return $this->notice;
    }

    public function getTotal(): int
    {
        return $this->error
            + $this->info
            + $this->warning
            + $this->emergency
            + $this->alert
            + $this->critical
            + $this->debug
            + $this->notice;
    }
}
