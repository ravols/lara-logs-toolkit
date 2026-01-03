<?php

namespace Ravols\LaraLogsToolkit\Tools;

use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class LogReader
{
    protected LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get the log file content
     *
     * @return string|null Log file content or null if file not found
     */
    public function getLogContent(): ?string
    {
        $logAnalyser = new LogAnalyser();
        $logFilePath = $logAnalyser->setLogger($this->logger)->getLogFilePath();

        if ($logFilePath === null || ! file_exists($logFilePath)) {
            return null;
        }

        $content = file_get_contents($logFilePath);

        if ($content === false || empty($content)) {
            return null;
        }

        return $content;
    }

    /**
     * Extract the last log entry of a specific level
     *
     * @param string $content Log file content
     * @param string $level Log level (ERROR, INFO, WARNING, etc.)
     * @param bool $withStackTrace If true, includes stack trace
     * @return string|null The last log entry or null
     */
    public function extractLastLogEntry(string $content, string $level, bool $withStackTrace = false): ?string
    {
        $lines = explode("\n", $content);
        $startIndex = null;

        $levelPattern = strtoupper($level);
        $pattern = '/\[.*?\]\s+\w+\.' . preg_quote($levelPattern, '/') . ':/';

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (preg_match($pattern, $lines[$i])) {
                $startIndex = $i;

                break;
            }
        }

        if ($startIndex === null) {
            return null;
        }

        if ($withStackTrace) {
            $endIndex = $this->findLogEntryEndIndex($lines, $startIndex);
            $logLines = array_slice($lines, $startIndex, ($endIndex - $startIndex + 1));

            return trim(implode("\n", $logLines));
        }

        $logLine = $lines[$startIndex];
        $logMessage = $this->extractLogMessage($logLine, $levelPattern);

        return $logMessage;
    }

    /**
     * Extract the last log entry of any level
     *
     * @param string $content Log file content
     * @param bool $withStackTrace If true, includes stack trace
     * @return string|null The last log entry or null
     */
    public function extractLastRecord(string $content, bool $withStackTrace = false): ?string
    {
        $lines = explode("\n", $content);
        $startIndex = null;

        $pattern = '/\[.*?\]\s+\w+\.(ERROR|INFO|WARNING|CRITICAL|ALERT|EMERGENCY|DEBUG|NOTICE):/';

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (preg_match($pattern, $lines[$i])) {
                $startIndex = $i;

                break;
            }
        }

        if ($startIndex === null) {
            return null;
        }

        if ($withStackTrace) {
            $endIndex = $this->findLogEntryEndIndex($lines, $startIndex);
            $logLines = array_slice($lines, $startIndex, ($endIndex - $startIndex + 1));

            return trim(implode("\n", $logLines));
        }

        $logLine = $lines[$startIndex];
        $logMessage = $this->extractLogMessage($logLine);

        return $logMessage;
    }

    /**
     * Find the end index of a log entry (where stack trace ends)
     *
     * @param array<string> $lines Log file lines
     * @param int $startIndex Start index of the log entry
     * @return int End index
     */
    protected function findLogEntryEndIndex(array $lines, int $startIndex): int
    {
        $inStackTrace = false;
        $endIndex = $startIndex;
        $consecutiveEmptyLines = 0;

        for ($i = $startIndex + 1; $i < count($lines); $i++) {
            $line = $lines[$i];
            $trimmedLine = trim($line);

            if (Str::contains(Str::lower($line), '[stack trace]')) {
                $inStackTrace = true;
                $endIndex = $i;
                $consecutiveEmptyLines = 0;

                continue;
            }

            if ($inStackTrace) {
                if (Str::match('/^#\d+\s+/', $trimmedLine) || (Str::startsWith($trimmedLine, '{') && Str::endsWith($trimmedLine, '}'))) {
                    $endIndex = $i;
                    $consecutiveEmptyLines = 0;

                    continue;
                }

                if (Str::isEmpty($trimmedLine)) {
                    $consecutiveEmptyLines++;
                    if ($consecutiveEmptyLines >= 2) {
                        break;
                    }

                    continue;
                }

                if (Str::match('/^\[.*?\]\s+\w+\.(ERROR|INFO|WARNING|CRITICAL|ALERT|EMERGENCY|DEBUG|NOTICE):/', $line)) {
                    break;
                }

                $endIndex = $i;
                $consecutiveEmptyLines = 0;
            } else {
                if (Str::match('/^\[.*?\]\s+\w+\.(ERROR|INFO|WARNING|CRITICAL|ALERT|EMERGENCY|DEBUG|NOTICE):/', $line)) {
                    break;
                }

                if (! Str::isEmpty($trimmedLine)) {
                    $endIndex = $i;
                }
            }
        }

        return $endIndex;
    }

    /**
     * Extract log message from a log line
     *
     * @param string $line Log line
     * @param string|null $level Log level (optional, for more specific extraction)
     * @return string|null Extracted log message
     */
    protected function extractLogMessage(string $line, ?string $level = null): ?string
    {
        $levelPattern = $level !== null ? preg_quote($level, '/') : '(?:ERROR|INFO|WARNING|CRITICAL|ALERT|EMERGENCY|DEBUG|NOTICE)';
        $pattern = '/\[.*?\]\s+\w+\.' . $levelPattern . ':\s*(.+)/';

        $message = Str::match($pattern, $line);

        if ($message === null) {
            return null;
        }

        $message = trim($message);

        return Str::contains($message, '{"exception"')
            ? trim(Str::before($message, '{"exception"'))
            : $message;
    }
}
