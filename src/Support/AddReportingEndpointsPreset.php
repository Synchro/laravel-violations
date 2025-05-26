<?php

namespace Synchro\Violation\Support;

use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Synchro\Violation\Violation;

class AddReportingEndpointsPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy->add(\Spatie\Csp\Directive::REPORT, Violation::getCspReportUri());
        $policy->add(\Spatie\Csp\Directive::REPORT_TO, Violation::getCspReportTo());
    }
}
