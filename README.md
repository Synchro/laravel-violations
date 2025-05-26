# CSP and NEL report handling for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/synchro/laravel-violations.svg?style=flat-square)](https://packagist.org/packages/synchro/laravel-violations)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/synchro/laravel-violations/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/synchro/laravel-violations/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/synchro/laravel-violations/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/synchro/laravel-violations/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/synchro/laravel-violations.svg?style=flat-square)](https://packagist.org/packages/synchro/laravel-violations)

The Content-Security-Policy (CSP) and Network Error Logging (NEL) HTTP headers provide ways to tell clients to report policy violations to a specified URL.

While CSP and NEL reports are generally benign in content, they represent a privacy leak if you point them at a third-party aggregation service. It reveals the fact that they are visiting your site, their IP, and their user-agent string to the third-party site, since reports are sent directly from the client's browser to the reporting service. Such leakage is flagged by [the Webbkoll privacy scanner](https://webbkoll.5july.net) for exactly this reason.

Another reason to do this is if the site you are deploying is on a private network that has no external internet access, so you need to store the reports locally, or forward them via a proxy service, or you won't see the reports at all.

This package provides a way to handle these reports in Laravel. It can store the reports in the database and/or forward them to a report aggregation service, such as [report-uri.com](https://report-uri.com), allowing you to manage the enormous volumes of reports that are often generated, without impacting the privacy of your users.

When using this package, all reports will appear to originate from your server's IP addresses, not your clients' browsers, and this means that things like geoIP country mapping will no longer be useful.
It does however preserve user-agent strings, letting you spot issues relating to specific browser platforms and versions.

While this package receives and understands reports, it doesn't do anything with them beyond store and forward them – that's up to you. Each report type has a DTO class that you can use to parse the report content, and you can listen for the events that are fired when reports are received, so it's easy for you to hook into these events and take further action.

[Examples of some unusual but interesting CSP reports](https://github.com/nico3333fr/CSP-useful/blob/master/csp-wtf/explained.md).

## Installation

Install the package with composer:

```bash
composer require synchro/laravel-violations
```

Publish the config file with:

```bash
php artisan vendor:publish --tag="violations-config"
```

Database support is optional; you may not want to keep the reports locally if you're forwarding them to an external report aggregation service. If you do want database support, set a table name in the config, then publish and run the migrations:

```bash
php artisan vendor:publish --tag="violations-migrations"
php artisan migrate
```

## Usage

It's worth reading [the docs on CSP violation reporting](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP#enabling_reporting).

There are two ways of defining target URLs to send reports to in CSP.

The preferred mechanism is [the `report-to` directive from CSP level 3](https://w3c.github.io/webappsec-csp/#directive-report-to) which targets
a [named reporting endpoint](https://www.w3.org/TR/reporting-1/) from a `Reporting-Endpoints` header, which can define one or more named endpoints to send reports to, like this:

```http header
Reporting-Endpoints: csp-report="https://example.com/csp", nel-report="https://example.com/nel"
Content-Security-Policy: default-src 'self'; report-to csp
```

The second mechanism is [the deprecated `report-uri` directive defined in CSP level 2](https://www.w3.org/TR/CSP2/#directive-report-uri), which includes the
reporting URL directly inside the CSP:

```http header
Content-Security-Policy: default-src 'self'; report-uri https://example.com/csp
```

One other difference is that `report-uri` can contain multiple URLs, whereas `report-to` can only contain a single target name. For compatibility with both at once, this package only supports creating a single reporting URL per named endpoint.

It's safe to define both directives; browsers that support `report-to` will ignore `report-uri` if it's also present, as per [the CSP level 3 spec](https://w3c.github.io/webappsec-csp/#directive-report-uri), and browsers that don't support `report-to` won't know what they're missing. The world is currently in a transition period where `report-uri` is deprecated, but support for `report-to` remains thin, so it's best to support both for now. Keep an eye on [caniuse.com](https://caniuse.com/mdn-http_headers_content-security-policy_report-to) for browser support updates.

If you configure the included `\Synchro\Violation\Http\Middleware\AddReportingHeaders` middleware, it will automatically add the `Reporting-Endpoints` and `Report-To` headers to your responses, so you don't need to do that yourself.

## Creating reporting headers

This package provides a middleware that will add both the `Reporting-Endpoints` and deprecated `Report-To` headers to your responses, either of which are required for the `report-to` directive to work in either CSP or NEL. You can add this middleware to your global middleware stack in `app/Http/Kernel.php`:

```php
\Synchro\Violation\Http\Middleware\AddReportingHeaders::class
```
You can also add it to specific routes or route groups if you only want it to apply to certain parts of your application.

## Creating your CSP header
This package does not generate your `Content-Security-Policy` header for you, but you can either create one manually or by using the [spatie/laravel-csp](https://packagist.org/packages/spatie/laravel-csp) package.

### Building your own CSP
You can set a CSP header manually on responses, and use this package to generate the correct value for the directives you need. For example, to set the `report-to` directive, you can do something like this in your controller or middleware:

```php
return response($content)
    ->header('Content-Security-Policy', 'default-src \'self\'; report-to '.Synchro\Violation\Violation::cspReportTo());
```

### Using `spatie/laravel-csp`

[Spatie's CSP package for Laravel](https://github.com/spatie/laravel-csp) helps you build complex CSP headers using a nice fluent interface. While it supports the `report-uri` and `report-to` directives, it doesn't actually create their values; That's where this package comes in.
In Spatie's CSP config fie in `config/csp.php`, set the `report-to` and `report-uri` CSP directives to retrieve correctly formatted reporting endpoints that you defined in this package's config, using the helper functions, for example:

```php
'report-uri' => \Synchro\Violation\Violation::cspReportUri(),
'report-to' => \Synchro\Violation\Violation::cspReportTo(),
```

There is also a `Spatie\Csp\Preset` class ready to use in `\Synchro\Violation\Support\AddReportingEndpointsPreset` which you can add to your CSP config to have it define the reporting directives for you.

## Network Error Logging (NEL)

This class also supports handling [Network Error Logging](https://www.w3.org/TR/network-error-logging/) reports,
which are sent when a client-side network error occurs, such as a failed connection to a CDN server or a DNS lookup failure.
You can set up an `NEL` header in your application that points at a named reporting endpoint defined in `Reporting-Endpoints` header, like this:

```
NEL: {"report-to": "nel"}
```

Just like CSP, creating this header is left up to you, but note that the `report-to` target URLs *are* managed by this package, so use the same names.

## Receiving reports
The package provides routes that you can use to receive reports. By default, is configured to receive both CSP and NEL reports at `/csp` and `/nel` respectively, but you can change them in the config file.

### Saving to the database
When a report is received, it is parsed and stored in the database if you have set a table name in the config file (and run the associated migration, described above). There is a Violation model defined in `\Synchro\Violation\Models\Violation` that you can use to query the reports stored in the database. The `report` field of this model contains the complete, unaltered report received from the client, and you can parse it using the [spatie/laravel-data](https://packagist.org/packages/spatie/laravel-data) DTO classes provided by this package (see below).

### Sending an event
On receiving a report, the package fires an event called `\Synchro\Violation\Events\Violation`, which you can listen for in your application to take further action, such as logging or alerting.

### Forwarding reports
You can also optionally forward the report to an external service such as [report-uri.com](https://report-uri.com) and this is done by dispatching a job called `\Synchro\Violation\Jobs\ForwardReport` that will send the report to the configured endpoint.

Even if you do not configure saving reports to the database, the package will still parse the reports and fire the event, so you can still take action on them without storing them locally.

### Parsing reports
While it's not the primary focus of this package, [spatie/laravel-data](https://packagist.org/packages/spatie/laravel-data) DTO classes are provided for each type of report that it can handle, complete with attribute-based validation, which you can use to parse the report content. For example, you can use `\Synchro\Violation\Reports\CSPReportData` to parse a CSP report (the interesting data will be in the wrapped `CSPReport`), and `\Synchro\Violation\Reports\NELReport` for an NEL report.

## Testing

Tests are written using [pest](https://pestphp.com). You can run them with:
```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](SECURITY.md) on how to report security vulnerabilities.

## Support open source development

This package was written by Marcus Bointon, [@Synchro on GitHub](https://github.com/Synchro), and is released under the MIT open-source license. If you rely on it, please consider becoming [a GitHub Sponsor](https://github.com/sponsors/Synchro).

- [Marcus Bointon](https://github.com/Synchro)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
