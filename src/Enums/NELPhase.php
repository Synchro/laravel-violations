<?php

declare(strict_types=1);

namespace Synchro\Violation\Enums;

enum NELPhase: string
{
    case DNS = 'dns';
    case CONNECTION = 'connection';
    case APPLICATION = 'application';
}
