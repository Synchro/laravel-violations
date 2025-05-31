<?php

declare(strict_types=1);

namespace Synchro\Violation;

use Synchro\Violation\Enums\ReportSource;

class Violation
{
    /**
     * Get the value for the CSP report-uri directive.
     * The report-uri directive is deprecated in level 3,
     * but use it at the same time as report-to to provide backward compatibility with level 2.
     */
    public static function cspReportUri(): string
    {
        return collect(config('violations.endpoints'))
            ->where('report_source', ReportSource::REPORT_URI)
            ->map(fn (array $endpoint) => self::resolveEndpointUrl($endpoint))
            ->implode(' ');
    }

    /**
     * Get the value for the CSP3 report-to directive.
     */
    public static function cspReportTo(): string
    {
        return collect(config('violations.endpoints'))
            ->where('report_source', ReportSource::REPORT_TO)
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
            ->map(function (array $endpoint) {
                $url = self::resolveEndpointUrl($endpoint);

                return $endpoint['name'].'="'.$url.'"';
            })
            ->implode(', ');
    }

    /**
     * Get the value for a Report-To header.
     * This header is deprecated but may still be used for CSP level 3 and NEL in browsers that are not up to speed.
     */
    public static function reportToHeaderValue(): string
    {
        return collect(config('violations.endpoints'))
            ->where('report_source', ReportSource::REPORT_TO)
            ->map(function (array $endpoint) {
                $url = self::resolveEndpointUrl($endpoint);

                return [
                    'group' => $endpoint['name'],
                    'max_age' => $endpoint['max_age'],
                    'endpoints' => [['url' => $url]],
                ];
            })
            ->values()
            ->toJson();
    }

    /**
     * Resolve an endpoint URL from configuration.
     * Supports multiple formats for backward compatibility:
     * - 'route_suffix': Combined with route_prefix to build route name (preferred for dynamic prefix support)
     * - 'route': Direct route name (for backward compatibility)
     * - 'url': Callable or string URL (for backward compatibility)
     */
    private static function resolveEndpointUrl(array $endpoint): string
    {
        if (isset($endpoint['route_suffix'])) {
            $routePrefix = config('violations.route_prefix', 'violations');
            $routeName = $routePrefix.'.'.$endpoint['route_suffix'];

            return route($routeName);
        }

        if (isset($endpoint['route'])) {
            return route($endpoint['route']);
        }

        if (isset($endpoint['url'])) {
            return is_callable($endpoint['url']) ? $endpoint['url']() : $endpoint['url'];
        }

        throw new \InvalidArgumentException('Endpoint must have either "route_suffix", "route", or "url" key');
    }
}
