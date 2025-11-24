<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\IPv4;
use Spatie\LaravelData\Attributes\Validation\IPv6;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Synchro\Violation\Enums\NELPhase;

/**
 * Class representing a Network Error Report body sent to a report-to URL in an NEL header.
 *
 * @see https://w3c.github.io/network-error-logging/#generate-a-network-error-report
 */
class NELBody extends ReportBody
{
    /**
     * @param  array<string, array<string>>  $requestHeaders
     * @param  array<string, array<string>>  $responseHeaders
     */
    public function __construct(
        // The sampling fraction used to determine this report.
        #[MapName('sampling_fraction'), Min(0), Max(1)]
        public readonly float $samplingFraction,
        // How long this took.
        #[MapName('elapsed_time'), Min(0)]
        public readonly int $elapsedTime,
        // How long after the request this happened.
        #[Min(0)]
        public readonly int $age,
        // The point in request processing that this happened.
        public readonly NELPhase $phase,
        // The type of the network error
        public readonly string $type = '',
        // The IP address of the server that was contacted.
        #[MapName('server_ip'), IPv4, IPv6]
        public readonly string $serverIp = '',
        // The protocol used to make the request.
        public readonly string $protocol = '',
        // The referrer attribute of the document where the violation occurred.
        public readonly string $referrer = '',
        // The HTTP method that triggered the error.
        public readonly string $method = '',
        // The HTTP status code received (can be 0 for non-HTTP errors, e.g. DNS).
        #[MapName('status_code'), Min(0), Max(599)]
        public readonly int $statusCode = 0,
        // The URL of the resource where the violation occurred.
        public readonly string $url = '',
        // The headers from the request
        #[MapName('request_headers')]
        public readonly array $requestHeaders = [],
        // The headers from the response
        #[MapName('response_headers')]
        public readonly array $responseHeaders = [],
    ) {}
}
