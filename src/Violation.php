<?php

declare(strict_types=1);

namespace Synchro\Violation;

class Violation
{
    /**
     * Get the value for the CSP report-uri directive.
     * This CSP directive is deprecated, but use it at the same time as report-to to provide backward compatibility.
     */
    public static function cspReportUri(): string
    {
        return collect(config('violations.endpoints'))
            ->where('type', 'csp')
            ->pluck('url')
            ->implode(' ');
    }

    /**
     * Get the value for the CSP report-to directive.
     * This directive is used for CSP level 2 and 3 client-side reporting.
     */
    public static function cspReportTo(): string
    {
        return collect(config('violations.endpoints'))
            ->where('type', 'csp')
            ->pluck('name')
            ->implode(' ');
    }

    /**
     * Get the value for a Reporting-Endpoints header.
     * This header is used for CSP level 2 and 3 client-side reporting, as well as NEL.
     */
    public static function reportingEndpointsHeaderValue(): string
    {
        return collect(config('violations.endpoints'))
            // Extract just the name and url from the endpoint list, format them as name=url
            ->map(function ($endpoint) {
                return $endpoint['name'].'='.$endpoint['url'];
            })
            ->implode(' ');
    }

    /**
     * Get the value for a Report-To header.
     * This header is used for CSP level 2 client-side reporting, but is deprecated in CSP level 3.
     */
    public static function reportToHeaderValue(): string
    {
        return collect(config('violations.endpoints'))
            ->map(function ($endpoint) {
                return [
                    'group' => $endpoint['name'],
                    'max_age' => $endpoint['max_age'],
                    'endpoints' => [['url' => $endpoint['url']]],
                ];
            })
            ->toJson();
    }
}
