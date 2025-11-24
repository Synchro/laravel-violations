# Changelog

# Laravel Violations 1.0.0 - 2025-11-24

* Added support for `csp-hash` and `permissions-policy-violation` reports, though csp-hash reports are only supported when using CSP3 `report-to` endpoints.

## Laravel Violations 1.0.0 - 2025-11-24

This is a feature and maintenance update that finally sets a 1.0 version.

Tests now include full PHP 8.5 builds and nightly PHP 8.6, and all dev deps have been bumped, including pest 4.0.

It adds support for `csp-hash` and `permissions-policy-violation` report types, both of which are supported by report-uri.com.

**Full Changelog**: https://github.com/Synchro/laravel-violations/compare/v0.1.0...v1.0.0

## Laravel Violations 0.1.0 - 2025-06-07

* The first release of a Laravel package to help you set up client-side reporting endpoint headers, and to handle the reports that they send in your app.
