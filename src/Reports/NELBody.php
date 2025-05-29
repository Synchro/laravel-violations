<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\IPv4;
use Spatie\LaravelData\Attributes\Validation\IPv6;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;

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
        #[MapInputName('sampling-fraction'), Min(0), Max(1)]
        readonly public float $samplingFraction = 1.0,
        // How long this took.
        #[MapInputName('elapsed-time'), Min(0)]
        readonly public int $elapsedTime = 0,
        // How long after the request this happened.
        #[MapInputName('age'), Min(0)]
        readonly public int $age = 0,
        // At what point in request processing this happened.
        readonly public string $phase = '',
        // The type of the network error
        readonly public string $type = '',
        // The IP address of the server that was contacted.
        #[MapInputName('server-ip'), IPv4, IPv6]
        readonly public string $serverIp = '',
        // The protocol used to make the request.
        readonly public string $protocol = '',
        // The referrer attribute of the document where the violation occurred.
        readonly public string $referrer = '',
        // The HTTP method that triggered the error.
        readonly public string $method = '',
        // The HTTP status code received (can be 0 for non-HTTP errors, e.g. DNS).
        #[MapInputName('status-code'), Min(0), Max(599)]
        readonly public int $statusCode = 0,
        // The URL of the resource where the violation occurred.
        readonly public string $url = '',
        // The headers from the request
        #[MapInputName('request-headers')]
        readonly public array $requestHeaders = [],
        // The headers from the response
        #[MapInputName('response-headers')]
        readonly public array $responseHeaders = [],
    ) {}
}
