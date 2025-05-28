<?php

declare(strict_types=1);

namespace Synchro\Violation\Enums;

/**
 * The supported NetworkReporting report types.
 */
enum NetworkReportingReportType: string
{
    case NEL = 'network-error';
    case CSP = 'csp-violation';

    public function label(): string
    {
        return match ($this) {
            self::NEL => 'network-error',
            self::CSP => 'csp-violation',
        };
    }
}
