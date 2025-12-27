<?php

namespace Ravols\LaraLogsToolkit\Enums;

enum DeleteLogAction: string
{
    case LATEST = 'latest';
    case ALL = 'all';

    public function label(): string
    {
        return match ($this) {
            self::LATEST => 'Delete only latest record',
            self::ALL => 'Delete all logs',
        };
    }
}
