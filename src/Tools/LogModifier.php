<?php

namespace Ravols\LaraLogsToolkit\Tools;

use Psr\Log\LoggerInterface;
use Ravols\LaraLogsToolkit\Enums\DeleteLogAction;

class LogModifier
{
    protected LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function delete(DeleteLogAction $action): void
    {
        $logAnalyser = new LogAnalyser();
        $logFile = $logAnalyser->setLogger($this->logger)->getLogFilePath();

        if ($logFile === null) {
            return;
        }

        match ($action) {
            DeleteLogAction::LATEST => $this->deleteLatestRecord($logFile),
            DeleteLogAction::ALL => $this->deleteAllLogs($logFile),
        };
    }

    private function deleteLatestRecord(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    private function deleteAllLogs(string $filePath): void
    {
        $directory = dirname($filePath);
        $basename = basename($filePath);
        $filenameWithoutExt = pathinfo($basename, PATHINFO_FILENAME);

        if (! is_dir($directory)) {
            return;
        }

        $filesToDelete = [];

        if (preg_match('/-\d{4}-\d{2}-\d{2}$/', $filenameWithoutExt)) {
            $basePattern = preg_replace('/-\d{4}-\d{2}-\d{2}$/', '', $filenameWithoutExt);
            $dailyFiles = glob($directory . '/' . $basePattern . '-*.log');

            if ($dailyFiles !== false) {
                $filesToDelete = array_merge($filesToDelete, $dailyFiles);
            }
        } else {
            $singleFile = $directory . '/' . $basename;
            if (file_exists($singleFile)) {
                $filesToDelete[] = $singleFile;
            }
        }

        $filesToDelete = array_unique($filesToDelete);

        foreach ($filesToDelete as $file) {
            if (file_exists($file) && is_file($file)) {
                unlink($file);
            }
        }
    }
}
