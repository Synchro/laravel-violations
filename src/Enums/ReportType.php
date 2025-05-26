<?php

declare(strict_types=1);

namespace Synchro\Violation\Enums;

/**
 * The supported violation report types.
 */
enum ReportType: int
{
    case REPORT_URI = 0;
    case REPORT_TO = 1;

    public function label(): string
    {
        return match ($this) {
            self::REPORT_URI => 'report-uri',
            self::REPORT_TO => 'report-to',
        };
    }
}
