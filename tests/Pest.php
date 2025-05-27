<?php

use Synchro\Violation\Tests\TestCase;

uses(TestCase::class)
    ->beforeEach(function () {
        Route::violations();
    })
    ->in(__DIR__);
