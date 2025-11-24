<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class CSP2Report extends Data
{
    // This MIME type is specific to CSP2 reports
    // https://www.w3.org/TR/CSP2/#violation-reports §4.4
    public const string MIME_TYPE = 'application/csp-report';

    public function __construct(
        #[MapInputName('csp-report')]
        public readonly CSP2ReportBody $cspReport
    ) {}
}
