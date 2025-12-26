<?php

namespace Ravols\LaraLogsToolkit\Tools;

use Psr\Log\LoggerInterface;
use Ravols\LaraLogsToolkit\Data\LogCountsData;

class LogAnalyser
{
    protected LoggerInterface $logger;
    protected array $logCounts = [
        'error' => 0,
        'info' => 0,
        'warning' => 0,
        'emergency' => 0,
        'alert' => 0,
        'critical' => 0,
        'debug' => 0,
        'notice' => 0,
    ];

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function analyzeLogs(): LogCountsData
    {
        $handlers = $this->getHandlers();

        foreach ($handlers as $handler) {
            $url = $this->getHandlerUrl($handler);

            if ($url === null) {
                continue;
            }

            $content = $this->readLogFile($url);

            if ($content === false || empty($content)) {
                $latestFile = $this->findLatestLogFile($url);
                if ($latestFile !== null) {
                    $content = $this->readLogFile($latestFile);
                }
            }

            if ($content === false || empty($content)) {
                continue;
            }

            $this->countLogLevels($content);
        }

        return LogCountsData::fromArray($this->logCounts);
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

    protected function findLatestLogFile(string $url): ?string
    {
        $filePath = $this->urlToFilePath($url);
        $directory = dirname($filePath);
        $basename = basename($filePath);

        if (! is_dir($directory)) {
            return null;
        }

        $files = glob($directory . '/' . pathinfo($basename, PATHINFO_FILENAME) . '-*.log');

        if (empty($files)) {
            $files = glob($directory . '/*.log');
        }

        if (empty($files)) {
            return null;
        }

        $latestFile = null;
        $latestTime = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $mtime = filemtime($file);
                if ($mtime > $latestTime) {
                    $latestTime = $mtime;
                    $latestFile = $file;
                }
            }
        }

        return $latestFile;
    }

    protected function countLogLevels(string $content): void
    {
        $this->logCounts['error'] += substr_count($content, '.ERROR');
        $this->logCounts['info'] += substr_count($content, '.INFO');
        $this->logCounts['warning'] += substr_count($content, '.WARNING');
        $this->logCounts['emergency'] += substr_count($content, '.EMERGENCY');
        $this->logCounts['alert'] += substr_count($content, '.ALERT');
        $this->logCounts['critical'] += substr_count($content, '.CRITICAL');
        $this->logCounts['debug'] += substr_count($content, '.DEBUG');
        $this->logCounts['notice'] += substr_count($content, '.NOTICE');
    }
}
