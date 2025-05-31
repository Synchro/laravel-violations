<?php

declare(strict_types=1);

namespace Synchro\Violation\Enums;

/**
 * The supported violation report sources.
 */
enum ReportSource: string
{
    case REPORT_URI = 'report-uri';
    case REPORT_TO = 'report-to';
}
