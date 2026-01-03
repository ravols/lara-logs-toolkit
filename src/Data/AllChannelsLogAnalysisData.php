<?php

namespace Ravols\LaraLogsToolkit\Data;

class AllChannelsLogAnalysisData
{
    public function __construct(
        public readonly array $channels = [],
    ) {
    }

    public function getChannel(string $channelName): ?LogCountsData
    {
        return $this->channels[$channelName] ?? null;
    }

    public function getChannels(): array
    {
        return $this->channels;
    }

    public function getError(): int
    {
        return array_sum(array_map(fn (LogCountsData $data) => $data->error, $this->channels));
    }

    public function getInfo(): int
    {
        return array_sum(array_map(fn (LogCountsData $data) => $data->info, $this->channels));
    }

    public function getWarning(): int
    {
        return array_sum(array_map(fn (LogCountsData $data) => $data->warning, $this->channels));
    }

    public function getEmergency(): int
    {
        return array_sum(array_map(fn (LogCountsData $data) => $data->emergency, $this->channels));
    }

    public function getAlert(): int
    {
        return array_sum(array_map(fn (LogCountsData $data) => $data->alert, $this->channels));
    }

    public function getCritical(): int
    {
        return array_sum(array_map(fn (LogCountsData $data) => $data->critical, $this->channels));
    }

    public function getDebug(): int
    {
        return array_sum(array_map(fn (LogCountsData $data) => $data->debug, $this->channels));
    }

    public function getNotice(): int
    {
        return array_sum(array_map(fn (LogCountsData $data) => $data->notice, $this->channels));
    }

    public function getTotal(): int
    {
        return array_sum(array_map(fn (LogCountsData $data) => $data->getTotal(), $this->channels));
    }

    public function toArray(): array
    {
        return [
            'channels' => array_map(fn (LogCountsData $data) => $data->toArray(), $this->channels),
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
            $channels[$channelName] = LogCountsData::fromArray($channelData);
        }

        return new self($channels);
    }
}
