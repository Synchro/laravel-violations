<?php

namespace Synchro\Violation;

class Violation
{
    /**
     * Get the value for the CSP report-uri directive.
     * This CSP directive is deprecated, but use it at the same time as report-to.
     */
    public static function getCspReportUri(): string
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
    public static function getCspReportTo(): string
    {
        return collect(config('violations.endpoints'))
            ->where('type', 'csp')
            ->pluck('name')
            ->implode(' ');
    }

    /**
     * Get the value for a Reporting-Endpoints header.
     * This header is used for CSP level 3 client-side reporting.
     */
    public static function getReportingEndpointsHeaderValue(): string
    {
        return collect(config('violations.endpoints'))
            // extract just the name and url from the endpoint list, format them as name=url
            ->map(function ($endpoint) {
                return $endpoint['name'].'='.$endpoint['url'];
            })
            ->implode(' ');
    }

    /**
     * Get the value for a Report-To header.
     * This header is used for CSP level 2 client-side reporting, but is now deprecated.
     */
    public static function getReportToHeaderValue(): string
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
