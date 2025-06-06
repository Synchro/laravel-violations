<?php

declare(strict_types=1);

namespace Synchro\Violation\Enums;

/**
 * Enumeration of phases in the Network Error Logging (NEL) reporting process.
 * See https://w3c.github.io/network-error-logging/#network-errors
 */
enum NELPhase: string
{
    case DNS = 'dns';
    case CONNECTION = 'connection';
    case APPLICATION = 'application';
}
