# Client-side report handling for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/synchro/laravel-violations.svg?style=flat-square)](https://packagist.org/packages/synchro/laravel-violations)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/synchro/laravel-violations/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/synchro/laravel-violations/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/synchro/laravel-violations/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/synchro/laravel-violations/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/synchro/laravel-violations.svg?style=flat-square)](https://packagist.org/packages/synchro/laravel-violations)

This package provides a way to configure client-side reporting endpoint headers and handle the reports that they generate in Laravel.

Client-side errors can be reported by the `Content-Security-Policy` (CSP), Network Error Logging (`NEL`), `Permissions-Policy`, and several other mechanisms.

These headers make use of the `report-to` directive from CSP level 3, which targets a [named reporting endpoint](https://www.w3.org/TR/reporting-1/), and the deprecated `report-uri` directive from CSP level 2, which includes the reporting URL directly inside the CSP.

Aside from CSP2's `report-uri` directive, the URLs themselves are defined in a `Reporting-Endpoints` header, which can define one or more named endpoints to send reports to. The similar `Report-To` header was defined in an earlier revision of the standard, but has since been removed from the spec. However, some browsers implemented it, and the NEL header only works with `Report-To`, so this package supports both mechanisms by default.

> [!TIP]
> You can find a good discussion of the differences between revisions of the Reporting API spec, and how to migrate between them on the [Chrome for developers blog](https://developer.chrome.com/blog/reporting-api-migration/).

This package can store the reports in the database and/or forward them to a report aggregation service, such as [report-uri.com](https://report-uri.com), allowing you to manage the enormous volumes of reports that are often generated without impacting the privacy of your users. Each report type has a DTO class that you can use to parse the report content.

It also generates events that you can listen for to take further action on the reports, such as logging or alerting.

While this package receives the reports, it doesn't do anything with them beyond store, parse, and forward them – anything more is up to you, but this may be all you need.

[Examples of some unusual but interesting CSP reports](https://github.com/nico3333fr/CSP-useful/blob/master/csp-wtf/explained.md).

## Installation

This package requires PHP 8.4 and Laravel 12 or later.

Install the package with composer:

```bash
composer require synchro/laravel-violations
```

Publish the config file with:

```bash
php artisan vendor:publish --tag="violations-config"
```

Database support is optional; you may not want to keep the reports locally if you're forwarding them to an external report aggregation service. If you do want database support, set a table name in the config first, *then* publish and run the migration (or the migration will be ignored):

```bash
php artisan vendor:publish --tag="violations-migrations"
php artisan migrate
```

## How reporting works

It's worth reading [the docs on CSP violation reporting](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP#enabling_reporting).

There are two ways of defining target URLs to send reports to in CSP.

The preferred mechanism is [the `report-to` directive from CSP level 3](https://w3c.github.io/webappsec-csp/#directive-report-to) which targets a [named reporting endpoint](https://www.w3.org/TR/reporting-1/) from a `Reporting-Endpoints` or `Report-To` header, which can define one or more named endpoints to send reports to.

The second mechanism is [the deprecated `report-uri` directive defined in CSP level 2](https://www.w3.org/TR/CSP2/#directive-report-uri), which includes the reporting URL directly inside the CSP:

```http header
Content-Security-Policy: default-src 'self'; report-uri https://example.com/csp
```

One other difference is that `report-uri` can contain multiple URLs, whereas `report-to` can only contain a single target name. For compatibility with both at once, *this package only supports creating a single reporting URL per named endpoint*.

It's safe to define both directives; browsers that support `report-to` will ignore `report-uri` if it's also present, as per [the CSP level 3 spec](https://w3c.github.io/webappsec-csp/#directive-report-uri), and browsers that don't support `report-to` won't know what they're missing. The world is currently in a transition period where `report-uri` is deprecated, but support for `report-to` remains thin, so it's best to support both for now. Keep an eye on [caniuse.com](https://caniuse.com/mdn-http_headers_content-security-policy_report-to) for browser support updates.

Here's a complete example using both headers, along with CSP and NEL headers using them, along with a `report-uri` directive for CSP level 2: 

```http header
Reporting-Endpoints: reports="https://example.com/report"
Report-To: {"group":"reports","max_age":86400,"endpoints":[{"url":"https://example.com/report"}]}, {"group":"nel","max_age":86400,"endpoints":[{"url":"https://example.com/nel"}]}
Content-Security-Policy: default-src 'self'; report-to reports; report-uri https://example.com/csp;
NEL: {"report-to": "nel"}
```

## Creating reporting headers
This package provides a middleware that will add both the `Reporting-Endpoints` and deprecated `Report-To` headers to your responses, either of which is required for the `report-to` directive to work in either CSP3 or NEL. You can add this as global middleware (so it will be added to all responses) in `bootstrap/app.php`:

```php
use \Synchro\Violation\Http\Middleware\AddReportingHeaders;
//...
->withMiddleware(function (Middleware $middleware) {
        $middleware->append(AddCspHeaders::class); //From Spatie's CSP package
        $middleware->append(AddReportingHeaders::class);
    })
```
You can also add it to specific routes or route groups if you only want it to apply to certain parts of your application.

## Creating your CSP header
This package does not generate your `Content-Security-Policy` header for you, but you can create one manually or using Spatie's [spatie/laravel-csp](https://packagist.org/packages/spatie/laravel-csp) package, and use this package to generate appropriate values to put in it.

### Building your own CSP
You can set a CSP header manually on responses and use this package to generate the correct value for the directives you need. For example, to set the `report-to` directive, you can do something like this in your controller or middleware:

```php
return response($content)
    ->header('Content-Security-Policy', 'default-src \'self\'; report-to '.Synchro\Violation\Violation::cspReportTo());
```

### Using `spatie/laravel-csp`
[Spatie's CSP package for Laravel](https://github.com/spatie/laravel-csp) helps you build complex CSP headers using a nice fluent interface. While it supports the `report-uri` and `report-to` directives, you are expected to populate their values yourself; That's where this package comes in.
In Spatie's CSP config file in `config/csp.php`, set the `report-to` and `report-uri` CSP directives to retrieve correctly formatted reporting endpoints that you defined in this package's config, using the helper functions, for example:

```php
'report-uri' => \Synchro\Violation\Violation::cspReportUri(),
'report-to' => \Synchro\Violation\Violation::cspReportTo(),
```

There is also a `Spatie\Csp\Preset` class ready to use in `\Synchro\Violation\Support\AddReportingEndpointsPreset` which you can add to your Spatie CSP config to have it define the reporting directives for you.

## Network Error Logging (NEL)
This class also handles [Network Error Logging](https://www.w3.org/TR/network-error-logging/) reports. These are sent when a client-side network error occurs, such as a DNS lookup failure, TCP or TLS handshake failure (e.g. your CDN's certificate expired), or application-level HTTP events like forwarding loops or user aborts.
You can set up an `NEL` header in your application that points at a named reporting endpoint defined in `Report-To` header, like this:

```
NEL: {"report-to": "nel"}
```

Just like CSP, creating this header is left up to you, but note that the `report-to` target URLs *are* managed by this package, so use the same names.

> [!WARNING]
> NEL *only* supports endpoints defined with the `Report-To` header; the NEL spec does not yet know about `Reporting-Endpoints`, so it's not supported that way (yet).

## Receiving reports – routes and controllers
The package provides a route macro that you can use to define all the routes you need to receive reports. By default, it is configured to receive CSP level 3 and NEL reports (and any other `report-to`-compatible mechanism) at `/violations/reports`, and CSP level 2 reports at `/violations/csp`. You can change these by setting the prefix in your `.env` file, and configuring the suffixes in the endpoints defined in config – read the config file for more details. The short version is to add this line to your `web.php` route file:

```php
Route::violations();
```

These routes point at a controller in `\Synchro\Violation\Http\Controllers\ViolationController` that provides a method to receive CSP2 reports called `csp()`, and one to handle CSP3, NEL, etc, called `reports()`.

Browsers will often send a preflight `OPTIONS` request to the reporting endpoint to check cross-origin permission (CORS) before sending the actual report; these routes are also set up for you, and also handled in the controller by the `options()` method.

> [!WARNING]
> None of these endpoints are intended to work directly in a browser; sending `GET` requests to reporting endpoints will result in `405 Method Not Allowed` errors.

When a report is received, it is parsed into a matching Data Transfer Object (DTO) built using Spatie's excellent [spatie/laravel-data](https://packagist.org/packages/spatie/laravel-data) package.

Be aware that the reporting mechanisms are deliberately designed to work "out of band" so that their traffic does not interfere with the performance of your site; the browser will accumulate reports and send them in batches after a delay, so you may receive multiple reports (possibly of multiple types) in a single request. The package will handle this for you and will parse each report individually, send events, store models, etc.

You can monitor the sending of reports in Chrome in its dev tools under the "Application" tab, then under the "Reporting API" section.

> [!TIP]
> Chrome accumulates reports for up to one minute before bundling them and sending them to reporting endpoints. For privacy reasons, only reports sharing the same source URL will be included in a bundle, so 2 issues on `page1.html` might be sent together, but reports from `page1.html` and `page2.html` will always be sent separately, even if they occurred within the same minute.

### Trustworthy endpoints
Testing report sending can be tricky because browsers are very picky about the circumstances under which they send reports. In particular, they will only send reports to TLS endpoints that conform to [a definition of "potentially trustworthy"](https://w3c.github.io/webappsec-secure-contexts/#is-origin-trustworthy), which may interfere with development practices, for example, it won't send reports to endpoints with self-signed certificates.

### Saving to the database
If you have set a table name in the config (and run the associated migration, described above), reports will be saved in your database.
There is a model defined in `\Synchro\Violation\Models\Violation` that you can use to query the reports stored in the database. The `report` field of this model contains the complete, unaltered report received from the client, and you can parse it using the provided DTO classes. Parsing and forwarding reports are independent of database storage – you don't have to store them, but if storage is not enabled, reports will be discarded if forwarding fails. See below for config.

### Events
On receiving a report, the package fires an event called `\Synchro\Violation\Events\Violation`, which you can listen for in your application to take further action, such as logging or alerting. The event carries a DTO for the report (not a model), so you can act on it directly.

### Forwarding
For each reporting endpoint you define in the config file, you can provide a URL of an external service such as [report-uri.com](https://report-uri.com). These forwarded reports are sent by dispatching a queued job called `\Synchro\Violation\Jobs\ForwardReport` that will send the report to the configured endpoint. There is also a global on/off switch for forwarding in the `VIOLATIONS_FORWARD_ENABLED` env variable.

If database storage is not enabled, the report will be queued for forwarding, but if forwarding fails, the report will be discarded. If storage is enabled, the package will attempt to forward the report up to three times, but if all attempts fail, the report will still be available in the database. You can override the retry limit in your config in the `violations.max_forward_attempts` property, or the `VIOLATIONS_MAX_FORWARD_ATTEMPTS` env variable.

The package configures a kernel task to retry forwarding failed reports every hour using an artisan command called `violations:queue`.

## Privacy concerns

While CSP and NEL reports are generally benign in content, they represent a privacy leak if you point them at a third-party aggregation service. Because reports are sent directly from the client's browser to the reporting service, it reveals the fact that someone is visiting your site, their IP, and their user-agent string, to the third-party site. Data leakage like this is flagged by [the Webbkoll privacy scanner](https://webbkoll.5july.net) for exactly this reason, and was one of the main reasons why I wrote this package.

When forwarding/proxying reports through this package to a reporting service, all reports will appear to originate from your server's IP addresses, not your clients' browsers. This means that things like geoIP country mapping will no longer work. It does, however, preserve user-agent strings, letting you spot issues relating to specific browser platforms and versions.

Another reason to proxy client-side reports is if your site is on a private network that has no external internet access. In that case you need to store the reports locally or forward them via a proxy service (such as this package provides), or you won't see the reports at all.

## Testing

Tests are written using [pest](https://pestphp.com). You can run them with:
```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Support open source development

This package was written by Marcus Bointon, [@Synchro on GitHub](https://github.com/Synchro), and is released under the MIT open-source license. If you rely on it, please consider becoming [a GitHub Sponsor](https://github.com/sponsors/Synchro).

![Report-uri.com](./report-uri-full.svg)
Development of this package was supported by [report-uri.com](https://report-uri.com), a service that provides a simple way to aggregate and summarise vast volumes of client-side reports including CSP and NEL.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
