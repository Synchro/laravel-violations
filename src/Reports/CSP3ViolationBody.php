<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Synchro\Violation\Enums\SecurityPolicyViolationEventDisposition;

/**
 * DTO representing the body of a Content Security Policy level-3 csp-violation report sent to
 * an endpoint referenced by a report-to CSP directive endpoint.
 * https://w3c.github.io/webappsec-csp/#csp-violation-report
 * https://w3c.github.io/webappsec-csp/#cspviolationreportbody
 */
class CSP3ViolationBody extends ReportBody
{
    public function __construct(
        // The address of the document where the violation occurred.
        #[MapName('documentURL')]
        readonly public string $documentURL = '',
        // The referrer attribute of the document where the violation occurred.
        readonly public string $referrer = '',
        // The URI that was blocked from loading due to the policy violation.
        #[MapName('blockedURL')]
        readonly public string $blockedURL = '',
        // The effective directive after applying the fallback directives (if any).
        #[MapName('effectiveDirective')]
        readonly public string $effectiveDirective = '',
        // The effective directive after applying the fallback directives (if any).
        #[MapName('violatedDirective')]
        readonly public string $violatedDirective = '',
        // The original policy as specified in the Content-Security-Policy HTTP header
        #[MapName('originalPolicy')]
        readonly public string $originalPolicy = '',
        // The URL of the resource where the violation occurred (for inline script and style violations).
        #[MapName('source-file')]
        readonly public ?string $sourceFile = null,
        // Script sample (for inline script and style violations)
        readonly public string $sample = '',
        // Violation’s disposition, "enforce" or "report"
        readonly public SecurityPolicyViolationEventDisposition $disposition = SecurityPolicyViolationEventDisposition::Enforce,
        // The HTTP status code of the resource on which the policy was applied
        #[MapName('status-code'), Min(0), Max(599)]
        readonly public int $statusCode = 0,
        // The line number in the source file where the violation occurred (for inline script and style violations)
        #[MapName('line-number'), Min(0)]
        readonly public ?int $lineNumber = null,
        // The column number in the source file where the violation occurred (for inline script and style violations)
        #[MapName('column-number'), Min(0)]
        readonly public ?int $columnNumber = null,
    ) {}
}
