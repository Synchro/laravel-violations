<?php

declare(strict_types=1);

namespace Synchro\Violation\Enums;

enum NELReportType: string
{
    case NETWORK_ERROR = 'network-error';
    case DISCONNECTED = 'disconnected';

    public function label(): string
    {
        return match ($this) {
            self::NETWORK_ERROR => 'network-error',
            self::DISCONNECTED => 'disconnected',
        };
    }
}
