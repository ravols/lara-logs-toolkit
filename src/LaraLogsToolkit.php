<?php

namespace Slovar\LaraLogsToolkit;

use Psr\Log\LoggerInterface;
use Slovar\LaraLogsToolkit\Tools\LogAnalyser;

class LaraLogsToolkit
{
    public function analyseLogs(LoggerInterface $logger): LogAnalyser
    {
        $logAnalyser = new LogAnalyser();
        $logAnalyser->setLogger($logger);
        $logAnalyser->analyzeLogs();

        return $logAnalyser;
    }

    public function countLogRecords(LoggerInterface $logger): ?int
    {
        try {
            $logAnalyser = $this->analyseLogs($logger);
            $counts = $logAnalyser->getCounts();

            return array_sum($counts);
        } catch (\Exception $e) {
            return null;
        }
    }
}
