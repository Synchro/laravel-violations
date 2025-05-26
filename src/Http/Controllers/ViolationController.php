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
use Synchro\Violation\Enums\ReportType;
use Synchro\Violation\Events\Violation as ViolationEvent;
use Synchro\Violation\Jobs\ForwardReport;
use Synchro\Violation\Models\Violation;
use Synchro\Violation\Reports\CSPReportData;
use Synchro\Violation\Reports\NELReport;

class ViolationController extends Controller
{
    use DispatchesJobs;
    use ValidatesRequests;

    private const ALLOWED_HEADERS = [
        'content-type',
        'origin',
        'accept',
        'user-agent',
        'authorization',
        'access-control-request-method',
        'access-control-request-headers',
    ];

    public function csp(Request $request): Response
    {
        if ($request->header('Content-Type') !== 'application/csp-report') {
            throw new BadRequestHttpException('Invalid Content-Type; must be \'application/csp-report\'');
        }
        try {
            $jsonData = json_decode($request->getContent(), true, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new BadRequestHttpException('Invalid JSON data: '.$e->getMessage());
        }
        // Manually create the DTO from the decoded JSON data
        $report = CSPReportData::from($request->getContent());
        // Make a violation model instance
        $violation = Violation::make([
            'report' => $report->toJson(),
            'report_type' => ReportType::REPORT_URI,
            'user_agent' => (config('violations.sanitize') ? null : $request->header('User-Agent')),
            'ip' => (config('violations.sanitize') ? null : $request->ip()),
        ]);
        if (config('violations.table')) {
            // If DB storage is enabled, store the report in the database
            // Store the report in the database
            $violation->save();
        }
        // If forwarding is enabled, dispatch a job to forward the report
        if (config('violation.forward')) {
            // Dispatch a job to forward the report
            $this->dispatch(new ForwardReport($violation));
        }
        ViolationEvent::dispatch($violation);

        return response()->noContent();
    }

    public function nel(NELReport $report): Response
    {
        // Convert the report into a violation model
        $violation = new Violation($report->toArray());
        // If DB storage is enabled, store the report in the database
        if (config('violation.store')) {
            // Store the report in the database
            $violation->save();
        }
        // If forwarding is enabled, dispatch a job to forward the report
        if (config('violation.forward')) {
            // Dispatch a job to forward the report
            $this->dispatch(new ForwardReport($violation));
        }
        ViolationEvent::dispatch($violation);

        return response()->noContent();
    }

    /**
     * Some browsers may send an OPTIONS request before sending a CSP report in a POST request,
     * so let the browser know that it's OK.
     */
    public function options(Request $request): Response
    {
        // Allow any origin and allow the OPTIONS method
        // Note that GET, POST, and HEAD are always allowed
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'OPTIONS',
        ];

        // If the request includes an Access-Control-Request-Headers header,
        // extract the allowed headers and set the Access-Control-Allow-Headers header
        if ($request->hasHeader('Access-Control-Request-Headers')) {
            if (! $this->validateAccessControlRequestHeaders($request->header('Access-Control-Request-Headers'))) {
                return response('Invalid headers requested', 400);
            }
            // If we get here, all the requested headers are in the allowlist, so just copy the whole thing back to the response
            $headers['Access-Control-Allow-Headers'] = $request->header('Access-Control-Request-Headers');
        }

        // Return the response with the appropriate headers and a 204 No Content status code
        return response('', 204, $headers);
    }

    private function validateAccessControlRequestHeaders(string $headerValue): bool
    {
        // Normalize allowed headers to lowercase
        $allowedHeaders = array_map('strtolower', self::ALLOWED_HEADERS);

        // Split the header value into individual headers
        $requestedHeaders = explode(',', $headerValue);

        // Trim and lowercase each requested header
        $requestedHeaders = array_map(function ($header) {
            return strtolower(trim($header));
        }, $requestedHeaders);

        // Check if all requested headers are in the allowed list
        foreach ($requestedHeaders as $header) {
            if (! in_array($header, self::ALLOWED_HEADERS)) {
                return false;
            }
        }

        return true;
    }
}
