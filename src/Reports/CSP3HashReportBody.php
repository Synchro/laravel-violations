<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use Spatie\LaravelData\Attributes\MapName;

/**
 * DTO representing the body of a Content Security Policy level-3 csp-hash report sent to
 * an endpoint referenced by a report-to CSP directive endpoint.
 * https://w3c.github.io/webappsec-csp/#csp-hash-report
 * https://w3c.github.io/webappsec-csp/#cspviolationreportbody
 */
class CSP3HashReportBody extends ReportBody
{
    public function __construct(
        // The address of the document where the violation occurred.
        #[MapName('document_url')]
        public readonly string $documentURL = '',
        // The referrer attribute of the document where the violation occurred.
        #[MapName('subresource_url')]
        public readonly string $subresourceURL = '',
        // The URI that was blocked from loading due to the policy violation.
        public readonly string $hash = '',
        // The effective directive after applying the fallback directives (if any).
        public readonly string $type = '',
        // The effective directive after applying the fallback directives (if any).
        public readonly string $destination = '',
    ) {}
}
