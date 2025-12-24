<?php

namespace Slovar\LaraLogsToolkit;

use Psr\Log\LoggerInterface;

class LaraLogsToolkit
{
    protected LoggerInterface $logger;
    protected array $logCounts = [
        'error' => 0,
        'info' => 0,
        'warning' => 0,
    ];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->analyzeLogs();
    }

    protected function analyzeLogs(): void
    {
        $handlers = $this->getHandlers();

        foreach ($handlers as $handler) {
            $url = $this->getHandlerUrl($handler);

            if ($url === null) {
                continue;
            }

            $content = $this->readLogFile($url);

            if ($content === false) {
                continue;
            }

            $this->countLogLevels($content);
        }
    }

    protected function getHandlers(): array
    {
        $monologLogger = $this->getMonologLogger();

        if ($monologLogger === null) {
            return [];
        }

        if (method_exists($monologLogger, 'getHandlers')) {
            return $monologLogger->getHandlers();
        }

        return [];
    }

    protected function getMonologLogger()
    {
        if (method_exists($this->logger, 'getHandlers')) {
            return $this->logger;
        }

        if (method_exists($this->logger, 'getLogger')) {
            return $this->logger->getLogger();
        }

        $reflection = new \ReflectionClass($this->logger);

        if ($reflection->hasMethod('getLogger')) {
            $method = $reflection->getMethod('getLogger');
            $monologLogger = $method->invoke($this->logger);

            if (is_object($monologLogger) && method_exists($monologLogger, 'getHandlers')) {
                return $monologLogger;
            }
        }

        // Try to get the underlying logger property
        if ($reflection->hasProperty('logger')) {
            $property = $reflection->getProperty('logger');
            $monologLogger = $property->getValue($this->logger);

            if (is_object($monologLogger) && method_exists($monologLogger, 'getHandlers')) {
                return $monologLogger;
            }
        }

        return null;
    }

    protected function getHandlerUrl($handler): ?string
    {
        if (! is_object($handler)) {
            return null;
        }

        if (method_exists($handler, 'getUrl')) {
            return $handler->getUrl();
        }

        $reflection = new \ReflectionClass($handler);

        if ($reflection->hasMethod('getUrl')) {
            $method = $reflection->getMethod('getUrl');

            return $method->invoke($handler);
        }

        // Try to get stream property (common in Monolog StreamHandler)
        if ($reflection->hasProperty('stream')) {
            $property = $reflection->getProperty('stream');
            $stream = $property->getValue($handler);

            if (is_resource($stream)) {
                $meta = stream_get_meta_data($stream);

                return $meta['uri'] ?? null;
            }
        }

        // Try to get url property (some handlers store URL directly)
        if ($reflection->hasProperty('url')) {
            $property = $reflection->getProperty('url');
            $url = $property->getValue($handler);

            if (is_string($url) && ! empty($url)) {
                return $url;
            }
        }

        return null;
    }

    protected function readLogFile(string $url): string|false
    {
        $filePath = $this->urlToFilePath($url);

        if (! file_exists($filePath)) {
            return false;
        }

        return file_get_contents($filePath);
    }

    protected function urlToFilePath(string $url): string
    {
        if (file_exists($url)) {
            return $url;
        }

        if (str_starts_with($url, 'file://')) {
            return substr($url, 7);
        }

        return $url;
    }

    protected function countLogLevels(string $content): void
    {
        $this->logCounts['error'] += substr_count($content, '.ERROR');
        $this->logCounts['info'] += substr_count($content, '.INFO');
        $this->logCounts['warning'] += substr_count($content, '.WARNING');
    }

    public function getErrorCount(): int
    {
        return $this->logCounts['error'];
    }

    public function getInfoCount(): int
    {
        return $this->logCounts['info'];
    }

    public function getWarningCount(): int
    {
        return $this->logCounts['warning'];
    }

    public function getAllCounts(): array
    {
        return $this->logCounts;
    }
}
