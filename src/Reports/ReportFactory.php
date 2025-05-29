<?php

namespace Synchro\Violation\Reports;

use Synchro\Violation\Enums\NetworkReportingReportType;

class ReportFactory 
{
    public static function from(array $data): NELReport|CSP3ViolationReport
    {
        if (!isset($data['type'])) {
            throw new \InvalidArgumentException('Report data must contain a "type" field');
        }

        $type = $data['type'];
        
        return match ($type) {
            NetworkReportingReportType::NEL->value, 'network-error' => NELReport::from($data),
            NetworkReportingReportType::CSP->value, 'csp-violation' => CSP3ViolationReport::from($data),
            default => throw new \InvalidArgumentException("Unsupported report type: {$type}"),
        };
    }
}