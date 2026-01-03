<?php

namespace Ravols\LaraLogsToolkit\Data;

class AllChannelsLogAnalysisData
{
    /**
     * @param array<string, LogCountsData|LogAnalysisComparisonData> $channels
     */
    public function __construct(
        public readonly array $channels = [],
    ) {
    }

    public function getChannel(string $channelName): LogCountsData|LogAnalysisComparisonData|null
    {
        return $this->channels[$channelName] ?? null;
    }

    public function getChannels(): array
    {
        return $this->channels;
    }

    private function getCountsData(LogCountsData|LogAnalysisComparisonData $data): LogCountsData
    {
        return $data instanceof LogAnalysisComparisonData ? $data->current : $data;
    }

    public function getError(): int
    {
        return array_sum(array_map(fn (LogCountsData|LogAnalysisComparisonData $data) => $this->getCountsData($data)->error, $this->channels));
    }

    public function getInfo(): int
    {
        return array_sum(array_map(fn (LogCountsData|LogAnalysisComparisonData $data) => $this->getCountsData($data)->info, $this->channels));
    }

    public function getWarning(): int
    {
        return array_sum(array_map(fn (LogCountsData|LogAnalysisComparisonData $data) => $this->getCountsData($data)->warning, $this->channels));
    }

    public function getEmergency(): int
    {
        return array_sum(array_map(fn (LogCountsData|LogAnalysisComparisonData $data) => $this->getCountsData($data)->emergency, $this->channels));
    }

    public function getAlert(): int
    {
        return array_sum(array_map(fn (LogCountsData|LogAnalysisComparisonData $data) => $this->getCountsData($data)->alert, $this->channels));
    }

    public function getCritical(): int
    {
        return array_sum(array_map(fn (LogCountsData|LogAnalysisComparisonData $data) => $this->getCountsData($data)->critical, $this->channels));
    }

    public function getDebug(): int
    {
        return array_sum(array_map(fn (LogCountsData|LogAnalysisComparisonData $data) => $this->getCountsData($data)->debug, $this->channels));
    }

    public function getNotice(): int
    {
        return array_sum(array_map(fn (LogCountsData|LogAnalysisComparisonData $data) => $this->getCountsData($data)->notice, $this->channels));
    }

    public function getTotal(): int
    {
        return array_sum(array_map(fn (LogCountsData|LogAnalysisComparisonData $data) => $this->getCountsData($data)->getTotal(), $this->channels));
    }

    public function getComparisonChannel(string $channelName): ?LogAnalysisComparisonData
    {
        $channel = $this->channels[$channelName] ?? null;

        return $channel instanceof LogAnalysisComparisonData ? $channel : null;
    }

    public function hasComparisonData(): bool
    {
        foreach ($this->channels as $data) {
            if ($data instanceof LogAnalysisComparisonData) {
                return true;
            }
        }

        return false;
    }

    public function toArray(): array
    {
        $channelsArray = [];
        foreach ($this->channels as $channelName => $data) {
            if ($data instanceof LogAnalysisComparisonData) {
                $channelsArray[$channelName] = [
                    'current' => $data->current->toArray(),
                    'cached' => $data->cached->toArray(),
                    'differences' => $data->differences->toArray(),
                    'logFileName' => $data->logFileName,
                    'cachedAt' => $data->cachedAt,
                ];
            } else {
                $channelsArray[$channelName] = $data->toArray();
            }
        }

        return [
            'channels' => $channelsArray,
            'totals' => [
                'error' => $this->getError(),
                'info' => $this->getInfo(),
                'warning' => $this->getWarning(),
                'emergency' => $this->getEmergency(),
                'alert' => $this->getAlert(),
                'critical' => $this->getCritical(),
                'debug' => $this->getDebug(),
                'notice' => $this->getNotice(),
                'total' => $this->getTotal(),
            ],
        ];
    }

    public static function fromArray(array $data): self
    {
        $channels = [];
        foreach ($data['channels'] ?? [] as $channelName => $channelData) {
            if (isset($channelData['current'], $channelData['cached'], $channelData['differences'])) {
                $channels[$channelName] = new LogAnalysisComparisonData(
                    current: LogCountsData::fromArray($channelData['current']),
                    cached: LogCountsData::fromArray($channelData['cached']),
                    differences: LogCountsData::fromArray($channelData['differences']),
                    logFileName: $channelData['logFileName'] ?? 'unknown',
                    cachedAt: $channelData['cachedAt'] ?? null,
                );
            } else {
                $channels[$channelName] = LogCountsData::fromArray($channelData);
            }
        }

        return new self($channels);
    }
}
