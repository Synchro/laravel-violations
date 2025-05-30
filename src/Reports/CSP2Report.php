<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

/**
 * DTO representing a Content Security Policy level-2 csp-report report sent to a report-uri URL in a CSP.
 *
 * @see https://www.w3.org/TR/CSP2/#violation-reports
 */
class CSP2Report extends Data
{
    public const string TYPE = 'csp-report';

    public function __construct(
        // The URI that was blocked from loading due to the policy violation.
        #[MapInputName('blocked-uri')]
        readonly public string $blockedURI = '',
        // The address of the document where the violation occurred.
        #[MapInputName('document-uri')]
        readonly public string $documentURI = '',
        // The name of the policy directive that was violated.
        // This will contain the directive whose enforcement triggered the violation (e.g. "script-src")
        // even if that directive does not explicitly appear in the policy, but is implicitly activated
        // via the default-src directive.
        #[MapInputName('effective-directive')]
        readonly public string $effectiveDirective = '',
        // The original policy as specified in the Content-Security-Policy HTTP header
        #[MapInputName('original-policy')]
        readonly public string $originalPolicy = '',
        // The referrer attribute of the document where the violation occurred.
        readonly public string $referrer = '',
        // The HTTP status code of the resource on which the policy was applied
        #[MapInputName('status-code'), Min(0), Max(599)]
        readonly public int $statusCode = 0,
        // The policy directive that was violated, as it appears in the policy.
        // This will contain the default-src directive in the case of violations caused by
        // falling back to the default sources when enforcing a directive
        #[MapInputName('violated-directive')]
        readonly public string $violatedDirective = '',
        // The URL of the resource where the violation occurred (for inline script and style violations).
        #[MapInputName('source-file')]
        readonly public string $sourceFile = '',
        // The line number in the source file where the violation occurred (for inline script and style violations)
        #[MapInputName('line-number'), Min(0)]
        readonly public int $lineNumber = 0,
        // The column number in the source file where the violation occurred (for inline script and style violations)
        #[MapInputName('column-number'), Min(0)]
        readonly public int $columnNumber = 0,
    ) {
        //
    }
}
