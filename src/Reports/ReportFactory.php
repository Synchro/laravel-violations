<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use InvalidArgumentException;
use Synchro\Violation\Enums\NetworkReportingReportType;

class ReportFactory
{
    public static function from(array $data): Report
    {
        if (! isset($data['type'])) {
            throw new InvalidArgumentException('Report data must contain a "type" field');
        }

        $type = $data['type'];

        return match ($type) {
            // If you want to add support for a new report type, this is the place to do it
            NetworkReportingReportType::NEL->value => NELReport::from($data),
            NetworkReportingReportType::CSP->value => CSP3ViolationReport::from($data),
            NetworkReportingReportType::CSPH->value => CSP3HashReport::from($data),
            NetworkReportingReportType::PPV->value => PermissionsPolicyReport::from($data),
            default => throw new InvalidArgumentException("Unsupported report type: $type"),
        };
    }
}
