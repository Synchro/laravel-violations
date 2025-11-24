<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use Spatie\LaravelData\Data;

abstract class Report extends Data
{
    // All Reporting API reports share the same MIME type,
    // so it's used for CSP3, NEL, etc.
    // @see https://w3c.github.io/reporting/#media-type
    public const string MIME_TYPE = 'application/reports+json';
}
