<?php

namespace Synchro\Violation\Reports;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

/**
 * Class representing a Network Error Logging report sent to a report-to URL in a CSP.
 *
 * @see https://www.w3.org/TR/reporting-1/#queue-report
 */
class NELReport extends Data
{
    public function __construct(
        // The network error report structure
        readonly public NELBody $body,
        // The client's user-agent string
        #[MapInputName('user-agent')]
        readonly public string $userAgent = '',
        // The target destination
        readonly public string $destination = '',
        // What kind of error report this is
        readonly public string $type = '',
        // The number of milliseconds between report generation and the time the error occurred
        #[Min(0)]
        readonly public int $age = 0,
        // The time this event occurred, milliseconds since epoch.
        #[Min(0)]
        readonly public int $timestamp = 0,
        // The number of times the client has attempted to send this report.
        #[Min(0)]
        readonly public int $attempts = 0,
        // The address of the document where the violation occurred.
        readonly public string $url = '',
    ) {}

    /**
     * @return array<string,string|array<string|Rule>>
     */
    public static function rules(): array
    {
        return [
            'body' => ['required', 'array'],
            'user_agent' => ['required', 'string'],
            'destination' => ['required', 'string'],
            'type' => ['required', 'string', 'in:network-error,disconnected'],
            'age' => ['integer', 'min:0'],
            'timestamp' => ['integer', 'min:0'],
            'attempts' => ['integer', 'min:0'],
            'url' => ['string', 'max:2048'],
        ];
    }
}
