<?php

declare(strict_types=1);

namespace Synchro\Violation\Enums;

/**
 * Permitted disposition values for CSP report-to reports.
 * Named to match the definition in the CSP3 specification.
 *
 * @see https://w3c.github.io/webappsec-csp/#enumdef-securitypolicyviolationeventdisposition
 */
enum SecurityPolicyViolationEventDisposition: string
{
    case Enforce = 'enforce';
    case Report = 'report';
}
