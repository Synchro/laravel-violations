<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

/**
 * DTO representing the body of a Deprecation report.
 * https://wicg.github.io/deprecation-reporting/#deprecation-report
 */
class DeprecationReportBody extends ReportBody
{
    public function __construct(
        // If known, the file which first used the indicated API, or null otherwise.
        public readonly ?string $sourceFile = null,
        // If known, the line number in sourceFile where the indicated API was first used, or null otherwise.
        public readonly ?int $lineNumber = null,
        // If known, the column number in sourceFile where the indicated API was first used, or null otherwise.
        public readonly ?int $columnNumber = null,
        // An implementation-defined string identifying the feature or API that will be removed.
        public readonly string $id = '',
        // A human-readable string with details typically matching what would be displayed on the developer console.
        public readonly string $message = '',
        // A Date object representing the date when the feature is expected to be removed from the current browser. If the date is not known, this property will return null.
        // Browsers serialise this as a JavaScript Date (ISO 8601, e.g. 2025-12-01).
        #[WithCast(DateTimeInterfaceCast::class, ['Y-m-d', 'Y-m-d\TH:i:s.v\Z', DATE_ATOM])]
        #[WithTransformer(DateTimeInterfaceTransformer::class, 'Y-m-d')]
        public readonly ?Carbon $anticipatedRemoval = null
    ) {}
}
