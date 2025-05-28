<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class CSP2ReportData extends Data
{
    public const string MIME_TYPE = 'application/csp-report';

    public function __construct(
        #[MapInputName('csp-report')]
        readonly public CSP2Report $cspReport
    ) {}
}
