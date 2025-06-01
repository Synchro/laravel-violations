<?php

declare(strict_types=1);

namespace Synchro\Violation\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use JsonException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Synchro\Violation\Enums\ReportSource;
use Synchro\Violation\Events\Violation as ViolationEvent;
use Synchro\Violation\Jobs\ForwardReport;
use Synchro\Violation\Models\Violation;
use Synchro\Violation\Reports\CSP2ReportData;
use Synchro\Violation\Reports\ReportFactory;

class ViolationController extends Controller
{
    use DispatchesJobs;
    use ValidatesRequests;

    private const array ALLOWED_HEADERS = [
        'content-type',
        'origin',
        'accept',
        'user-agent',
        'authorization',
        'access-control-request-method',
        'access-control-request-headers',
    ];

    /**
     * Handle a report submission from a CSP level 2 report-uri endpoint.
     */
    public function csp(Request $request): Response
    {
        if ($request->header('Content-Type') !== 'application/csp-report') {
            throw new BadRequestHttpException('Invalid Content-Type; must be \'application/csp-report\'');
        }
        try {
            $jsonData = json_decode(
                json: $request->getContent(),
                associative: true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid JSON data');
        }
        // Manually create the DTO from the decoded JSON data
        $report = CSP2ReportData::from($request->getContent());

        $userAgent = config('violations.sanitize') ? null : $request->header('User-Agent');
        $ip = config('violations.sanitize') ? null : $request->ip();

        $violationId = null;
        if (config('violations.table')) {
            // If DB storage is enabled, store the report in the database
            $violation = new Violation([
                'report' => $report->toJson(),
                'report_source' => ReportSource::REPORT_URI,
                'user_agent' => $userAgent,
                'ip' => $ip,
            ]);
            $violation->save();
            $violationId = $violation->id;
        }

        // Check if forwarding is enabled and find the appropriate endpoint configuration
        if (config('violations.forward_enabled')) {
            $forwardTo = $this->getForwardingUrlForReportSource(ReportSource::REPORT_URI);
            if ($forwardTo) {
                $this->dispatch(new ForwardReport($report, ReportSource::REPORT_URI, $forwardTo, $userAgent, $ip, $violationId));
            }
        }

        ViolationEvent::dispatch($report, ReportSource::REPORT_URI, $userAgent, $ip);

        return response()->noContent();
    }

    /**
     * Handle a report submission from a CSP level 3 report-to endpoint, and any other service that uses this format.
     */
    public function reports(Request $request): Response
    {
        // Validate content type
        if ($request->header('Content-Type') !== 'application/reports+json') {
            throw new BadRequestHttpException('Content-Type must be application/reports+json');
        }

        try {
            $jsonData = json_decode(
                json: $request->getContent(),
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $e) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid JSON data');
        }

        $userAgent = config('violations.sanitize') ? null : $request->header('User-Agent');
        $ip = config('violations.sanitize') ? null : $request->ip();

        // Check if we have multiple reports or a single report
        if (self::isArrayOfReports($jsonData)) {
            // Handle multiple reports in a single request
            foreach ($jsonData as $reportData) {
                $this->processReport($reportData, $userAgent, $ip);
            }
        } else {
            // Handle single report
            $this->processReport($jsonData, $userAgent, $ip);
        }

        return response()->noContent();
    }

    /**
     * Browsers may send an OPTIONS request before sending a report in a POST request,
     * so let the browser know that it's OK.
     */
    public function options(Request $request): Response
    {
        // Allow any origin and allow the OPTIONS method
        // Note that GET, POST, and HEAD are always allowed, so we don't need to list them
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'OPTIONS',
        ];

        // If the request includes an Access-Control-Request-Headers header,
        // extract the allowed headers and set the Access-Control-Allow-Headers header
        if ($request->hasHeader('Access-Control-Request-Headers')) {
            if (! self::validateAccessControlRequestHeaders($request->header('Access-Control-Request-Headers'))) {
                return response('Invalid headers requested', 400);
            }
            // If we get here, all the requested headers are in the allowlist, so just copy the whole thing back to the response
            $headers['Access-Control-Allow-Headers'] = $request->header('Access-Control-Request-Headers');
        }

        // Return the response with the appropriate headers and a 204 No Content status code
        return response('', 204, $headers);
    }

    private static function validateAccessControlRequestHeaders(string $headerValue): bool
    {
        // Normalize allowed headers to lowercase
        $allowedHeaders = array_map('strtolower', self::ALLOWED_HEADERS);

        // Split the header value into individual headers
        $requestedHeaders = explode(',', $headerValue);

        // Trim and lowercase each requested header
        $requestedHeaders = array_map(function (string $header) {
            return strtolower(trim($header));
        }, $requestedHeaders);

        // Check if all requested headers are in the allowed list
        return array_all($requestedHeaders, fn (string $header) => in_array($header, self::ALLOWED_HEADERS));
    }

    /**
     * Process a single report from the report-to endpoint.
     * Handles storage, forwarding, and event dispatch for individual reports.
     */
    private function processReport(array $reportData, ?string $userAgent, ?string $ip): void
    {
        // Use ReportFactory to parse the report (supports CSP3, NEL, and future formats)
        $report = ReportFactory::from($reportData);

        $violationId = null;
        if (config('violations.table')) {
            // If DB storage is enabled, store the report in the database
            $violation = new Violation([
                'report' => $report->toJson(),
                'report_source' => ReportSource::REPORT_TO,
                'user_agent' => $userAgent,
                'ip' => $ip,
            ]);
            $violation->save();
            $violationId = $violation->id;
        }

        // Check if forwarding is enabled and find the appropriate endpoint configuration
        if (config('violations.forward_enabled')) {
            $forwardTo = $this->getForwardingUrlForReportSource(ReportSource::REPORT_TO);
            if ($forwardTo) {
                $this->dispatch(new ForwardReport($report, ReportSource::REPORT_TO, $forwardTo, $userAgent, $ip, $violationId));
            }
        }

        ViolationEvent::dispatch($report, ReportSource::REPORT_TO, $userAgent, $ip);
    }

    /**
     * Get the forwarding URL for a specific report source from the endpoint configuration.
     */
    private function getForwardingUrlForReportSource(ReportSource $reportSource): ?string
    {
        $endpoints = config('violations.endpoints', []);

        foreach ($endpoints as $endpoint) {
            if (isset($endpoint['report_source']) && $endpoint['report_source'] === $reportSource) {
                return $endpoint['forward_to'] ?? null;
            }
        }

        return null;
    }

    /**
     * Reports from CSP & NEL report-to endpoints can contain either a bare report object or an array of multiple report objects.
     * Check whether the array is multiple reports or just one.
     */
    private static function isArrayOfReports(array $reports): bool
    {
        // Check if the array is empty or contains only arrays
        return ! empty($reports) && collect($reports)->every(fn (mixed $report) => is_array($report));
    }
}
