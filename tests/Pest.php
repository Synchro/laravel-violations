<?php

use Synchro\Violation\Tests\TestCase;

uses(TestCase::class)
    ->beforeEach(function () {
        Route::violations();
    })
    ->in(__DIR__);

// Check that all keys and values in $array are present in $this->value,
// in any order
expect()->extend('toBeASupersetOf', function ($array) {
    expect($this->value)
        ->toBeArray()
        ->and($array)->toBeArray();

    foreach ($array as $key => $value) {
        expect($this->value)->toHaveKey($key);

        if (is_array($value) && is_array($this->value[$key])) {
            // Check nested arrays recursively
            expect($this->value[$key])->toBeASupersetOf($value);
        } else {
            expect($this->value[$key])->toBe($value);
        }
    }

    return $this;
});

// Check that all keys and values in $this->value are present in $array,
// in any order
expect()->extend('toBeASubsetOf', function ($array) {
    expect($this->value)
        ->toBeArray()
        ->and($array)->toBeArray();

    foreach ($this->value as $key => $value) {
        expect($array)->toHaveKey($key);

        if (is_array($value) && is_array($array[$key])) {
            // Check nested arrays recursively
            expect($value)->toBeASubsetOf($array[$key]);
        } else {
            expect($array[$key])->toBe(($value));
        }
    }

    return $this;
});
