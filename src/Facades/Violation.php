<?php

namespace Synchro\Violation\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Synchro\Violation\Violation
 */
class Violation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Synchro\Violation\Violation::class;
    }
}
