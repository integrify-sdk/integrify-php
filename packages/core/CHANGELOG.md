# Changelog

All notable changes to `integrify/core` are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/) and this project follows
[Semantic Versioning](https://semver.org/).

> **Pre-1.0.** Under Composer's caret rules a `0.x` minor is the breaking position:
> `^0.1` resolves to `>=0.1.0 <0.2.0`, so **any** `0.2.0` release breaks every consumer
> pinned at `^0.1`. Expect the surface to move until `1.0.0`.

## [0.1.0] - 2026-09-17

First release.

### Client

- `Client` — base class for integration clients, supplying base-url handling, default
  headers and `get()` / `post()` / `put()` / `delete()` / `send()` helpers. Endpoints are
  written as ordinary typed methods; there is no magic dispatch.
- `Client::uri($template, $params)` — URI templating. `{name}` placeholders are filled
  from `$params` and `rawurlencode`d, so a dynamic path segment cannot change the meaning
  of the URL. This is the PHP counterpart of Python's `URL_PARAM_FIELDS` /
  `set_urlparams`. A path containing a raw `?` or `#` is refused: query parameters belong
  in `$query`.
- Absolute URLs are accepted only on the base URL's host. `defaultHeaders()` typically
  carries the API key, so a path that redirected the request elsewhere would leak it.
  `assertSameHost()` is the hook for widening this.
- Headers merge case-insensitively (`mergeHeaders()`), and `Content-Type` is sent only
  when the request actually has a body.
- `listBody()` / `objectBody()` for the empty-array-vs-empty-object distinction PHP
  cannot express on its own.

### Transport

- `Transport` interface with two implementations: `HttpTransport` (PSR-18 / PSR-17, with
  a `php-http/discovery` fallback and PSR-3 logging) and `RecordingTransport` for tests
  and payload inspection.
- Both honour the same contract: HTTP >= 400 and network failures raise `RequestFailed`,
  and a body that cannot be JSON-encoded (`INF`, `NAN`, invalid UTF-8) raises in both, so
  that failure mode cannot hide until production.
- `RecordingTransport` raises on a request that arrives with an empty queue and no
  explicit fallback, so a test asserting a single API call cannot quietly pass when the
  code makes four. Pass a fallback response to the constructor to opt out.
- `HttpTransport` wraps every foreign exception — PSR-7 factory errors, discovery
  failures, JSON encoding failures, and anything a non-conforming PSR-18 middleware
  throws — so nothing that is not an `IntegrifyException` escapes the library.

### Request / Response

- `Request` — immutable description of an outgoing request, with the body still an array
  so tests can assert on it directly.
- `Response` — `status`, `headers`, `body`, plus `to()` / `toList()` for hydrating DTOs,
  `header()` / `headerValues()` for repeatable headers, and a lenient `toArray()` that
  returns `[]` rather than throwing on a non-JSON error page.
- `toList()` requires a JSON array at the root; a JSON object raises rather than having
  its keys silently discarded.

### DTO layer

- `Dto\Data` — `readonly` DTO base class with an attribute-driven reflection mapper:
  `#[Field]` names, nested DTOs, backed and pure enums, type coercion and constraint
  validation, all errors collected into a single `ValidationFailed`.
- Both the PHP property name and the API field name are accepted on input.
- `#[Field(of: ...)]` must name a `Data` subclass or an enum; anything else raises a
  `LogicException` at describe time rather than silently disabling element validation.
- Union-typed properties try every member, not just the first.
- Numeric coercion never truncates or saturates: fractional values, `NAN`, `INF` and
  anything outside the integer range are rejected.
- Lengths are measured in characters, not bytes.
- Per-class metadata is reflected once and cached.

### Errors

- `Exception\IntegrifyException` hierarchy: `ValidationFailed`, `InvalidRequest`,
  `RequestFailed`, `MissingConfiguration`.

### Environment

- `Environment` enum with an alias-aware `parse()` (`prod` / `production` / `live`;
  `test` / `testing` / `sandbox` / `dev` / `development` / `local`) and a `strict:` flag
  that raises `MissingConfiguration` on an unrecognised value.

[0.1.0]: https://github.com/Integrify-SDK/integrify-php/releases/tag/core-0.1.0
