<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Synchro\Violation\Enums\SecurityPolicyViolationEventDisposition;

/**
 * DTO representing a Content Security Policy level-3 csp-violation report sent to
 * a URL referenced by a report-to CSP directive endpoint.
 * https://w3c.github.io/webappsec-csp/#csp-violation-report
 * https://w3c.github.io/webappsec-csp/#cspviolationreportbody
 */
class CSP3ViolationReportBody extends Data
{
    public const string MIME_TYPE = 'application/csp-report';

    public const string TYPE = 'csp-violation';

    public function __construct(
        // The address of the document where the violation occurred.
        #[MapInputName('document-uri')]
        readonly public string $documentUri = '',
        // The referrer attribute of the document where the violation occurred.
        readonly public string $referrer = '',
        // The URI that was blocked from loading due to the policy violation.
        #[MapInputName('blocked-uri')]
        readonly public string $blockedUri = '',
        // The effective directive after applying the fallback directives (if any).
        #[MapInputName('effective-directive')]
        readonly public string $effectiveDirective = '',
        // The effective directive after applying the fallback directives (if any).
        #[MapInputName('violated-directive')]
        readonly public string $violatedDirective = '',
        // The original policy as specified in the Content-Security-Policy HTTP header
        #[MapInputName('original-policy')]
        readonly public string $originalPolicy = '',
        // The URL of the resource where the violation occurred (for inline script and style violations).
        #[MapInputName('source-file')]
        readonly public ?string $sourceFile = null,
        // Script sample (for inline script and style violations)
        readonly public string $sample = '',
        // Violation’s disposition, "enforce" or "report"
        readonly public SecurityPolicyViolationEventDisposition $disposition = SecurityPolicyViolationEventDisposition::Enforce,
        // The HTTP status code of the resource on which the policy was applied
        #[MapInputName('status-code'), Min(0), Max(599)]
        readonly public int $statusCode = 0,
        // The line number in the source file where the violation occurred (for inline script and style violations)
        #[MapInputName('line-number'), Min(0)]
        readonly public ?int $lineNumber = null,
        // The column number in the source file where the violation occurred (for inline script and style violations)
        #[MapInputName('column-number'), Min(0)]
        readonly public ?int $columnNumber = null,
    ) {}
}
