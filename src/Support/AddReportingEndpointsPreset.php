<?php

declare(strict_types=1);

namespace Synchro\Violation\Support;

use Spatie\Csp\Directive;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Synchro\Violation\Violation;

/**
 * A preset that adds reporting endpoints for CSP violation reports using Spatie's CSP package,
 * So add that dependency if you want to make use of this preset.
 */
class AddReportingEndpointsPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy->add(Directive::REPORT, Violation::cspReportUri());
        $policy->add(Directive::REPORT_TO, Violation::cspReportTo());
    }
}
