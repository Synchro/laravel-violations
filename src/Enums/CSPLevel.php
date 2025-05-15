<?php

namespace Synchro\Violation\Enums;

/**
 * The supported CSP level versions.
 */
enum CSPLevel: int
{
    case LEVEL2 = 2;
    case LEVEL3 = 3;

    public function label(): string
    {
        return match ($this) {
            self::LEVEL2 => 'CSP level 2',
            self::LEVEL3 => 'CSP level 3',
        };
    }
}
