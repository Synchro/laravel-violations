# CSP and NEL report handling for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/synchro/laravel-violations.svg?style=flat-square)](https://packagist.org/packages/synchro/laravel-violations)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/synchro/laravel-violations/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/synchro/laravel-violations/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/synchro/laravel-violations/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/synchro/laravel-violations/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/synchro/laravel-violations.svg?style=flat-square)](https://packagist.org/packages/synchro/laravel-violations)

The Content-Security-Policy (CSP) and Network Error Logging (NEL) HTTP headers provide ways to tell clients to report policy violations to a specified URL.

While CSP and NEL reports are generally benign in content, they represent a privacy leak if you point them at a third-party aggregation service because it reveals the client's IP and user-agent string to the third-party site, since reports are sent directly from the client's browser to the reporting service. Such leakage is flagged by [the Webbkoll privacy scanner](https://webbkoll.5july.net) for exactly this reason.

Another reason to do this is if the site you are deploying is on a private network that has no external internet access, so you need to store the reports locally, or forward them via a proxy service, or you won't see the reports at all.

This package provides a way to handle these reports in Laravel. It can store the reports in the database and/or forward them to a report aggregation service, such as [report-uri.com](https://report-uri.com), allowing you to manage the enormous volumes of reports that are often generated, without impacting the privacy of your users.

When using this package, all reports will appear to originate from your server's IP addresses, not your clients' browsers, and this means that things like geoIP country mapping will no longer be useful.
It does however preserve user-agent strings, letting you spot issues relating to specific browser platforms and versions.

While this package receives and understands reports, it doesn't do anything with them beyond store and forward them – that's up to you. Each report type has a DTO class that you can use to parse the report content, and you can listen for the events that are fired when reports are received, so it's easy for you to hook into these events and take further action.

[Examples of some unusual but interesting CSP reports](https://github.com/nico3333fr/CSP-useful/blob/master/csp-wtf/explained.md).

## Creating your CSP header

This package does not generate your `Content-Security-Policy` header, but you can generate one either manually or by using the [spatie/laravel-csp](https://packagist.org/packages/spatie/laravel-csp) package, which provides a fluent interface for building a CSP header. Using Spatie's package, you should set the `report-to` directive to point at the reporting endpoint you define in your application by setting the `report-to` option in the `config/csp.php` config file, for example:

```php
'report_uri' => url('csp-report'),
```

## Support us

This package was written by Marcus Bointon, [@Synchro on GitHub](https://github.com/Synchro), and is released under the MIT open-source license. If you rely on it, please consider becoming [a GitHub Sponsor](https://github.com/sponsors/Synchro).
[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-violations.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-violations)

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

```http request
Reporting-Endpoints: csp-report="https://example.com/csp-report", nel-report="https://example.com/nel-report"
Content-Security-Policy: default-src 'self'; report-to csp-report
```

The second mechanism is [the deprecated `report-uri` directive defined in CSP level 2](https://www.w3.org/TR/CSP2/#directive-report-uri), which includes the
reporting URL directly inside the CSP:

```http request
Content-Security-Policy: default-src 'self'; report-uri https://example.com/report-uri
```

One other difference is that `report-uri` can contain multiple URLs, whereas `report-to` can only contain a single target name.

It's safe to define both directives; browsers that support `report-to` will prioritise it over `report-uri`, as per [the CSP level 3 spec](https://w3c.github.io/webappsec-csp/#directive-report-uri).

Note that this class does not support [the deprecated `Report-To` header](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Report-To), though be sure not to confuse that with the `report-to` CSP directive; they are different things.

### Using Spatie's CSP package
[Spatie's CSP package](https://github.com/spatie/laravel-csp) helps you build complex CSP headers using a nice fluent interface. While it supports the `report-uri` and `report-to` directives, it doesn't actually create their values. That's where this package comes in.

For `report-to` (where the param is the name of your defined endpoint for CSP reports), set it like this:
```php
->addDirective(Directive::REPORT_TO, 'csp-report')
```
and for the old `report-uri` (where the parameter given to `url()` is the name of your CSP reporting route), like this:

```php
->addDirective(Directive::REPORT, url('csp-report'))
```

### Network Error Logging (NEL)
This class also supports handling [Network Error Logging](https://www.w3.org/TR/network-error-logging/) reports,
which are sent when a client-side network error occurs, such as a failed connection to a server or a DNS lookup failure.
You can set up an `NEL` header in your application that points at the reporting route in your application, like this:

```http request
NEL: {"report-to": "nel-reports"}
```

Note that since the point of NEL is to report connectivity issues with your application from the client's perspective,
pointing it the same application it's having trouble connecting to is unlikely to be useful! Ideally the NEL reporting
endpoint should be handled by a different app running on separate hosting.

## Events
This package generates events when it receives a report. You can listen for these events in your application to take further action by creating a listener for the `\Synchro\Violations\Events\Violation` event.

## Nova support
This package optionally includes a [Laravel Nova](https://nova.laravel.com) resource for viewing and managing reports. To use it, you must have Nova installed in your application. Install it with artisan:

```bash
php artisan vendor:publish --tag="laravel-violations-nova"
```
You'll then need to add it to your Nova resource list by adding it to your `NovaServiceProvider`:

```php
use Synchro\Violations\Nova\Violation;
//...
```

## Filament support

This package optionally includes a [Filament](https://filamentphp.com) resource for viewing and managing reports.
To use it, you must have Filament installed in your application. Install it with artisan:

```bash
php artisan vendor:publish --tag="laravel-violations-filament"
```

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

## Credits

- [Marcus Bointon](https://github.com/Synchro)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
