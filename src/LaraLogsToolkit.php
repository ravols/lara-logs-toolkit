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
}
