# Changelog

All notable changes to `integrify/kapitalbank` are documented here. The format is based
on [Keep a Changelog](https://keepachangelog.com/) and this project follows
[Semantic Versioning](https://semver.org/).

> **Pre-1.0.** Under Composer's caret rules a `0.x` minor is the breaking position:
> `^0.1` resolves to `>=0.1.0 <0.2.0`.

## [0.1.0] - 2026-09-17

First release. Ported from
[`integrify-kapitalbank`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/kapitalbank)
(Python), same wire protocol, PHP-shaped API.

All ten request payloads were diffed field-for-field against the ones the Python
package's pydantic models produce for the same arguments; every one is identical.

### Added

- `KapitalClient` — `createOrder()`, `getOrderInformation()`,
  `getDetailedOrderInformation()`, `refundOrder()`, `saveCard()`, `payAndSaveCard()`,
  `fullReverseOrder()`, `clearingOrder()`, `partialReverseOrder()`,
  `orderWithSavedCard()`, `linkCardToken()`, `processPaymentWithSavedCard()`, and
  `payWithSavedCard()` which chains the last three.
- `KapitalConfig` — immutable credentials value object with `fromEnvironment()`, and
  the test/production base URL derived from `Environment`.
- `OrderStatus` and `ErrorCode` enums, plus `PmoResultCode` for the bank's PMO result
  codes, exposed through `status()` / `resultMessage()` accessors rather than used as
  DTO property types, so an unrecognised upstream value cannot break validation.
- `RequestRejected` — the bank's `errorCode` / `errorDescription`, raised rather than
  returned.

### Differences from the Python package

- **A rejected request raises `RequestRejected`.** Kapital Bank signals failure with an
  HTTP status, not a field, so this follows the repo's rule that HTTP-level failures
  throw. Python returns `APIResponse.ok = false` with the error in `body.error`, which
  is easy to forget to check. The original `RequestFailed` is kept as `getPrevious()`,
  and a *network* failure stays `RequestFailed` — a dead connection is not a rejection.
- **`pmoResultCode` keeps its raw value.** Python's validator writes
  `PMO_RESULT_CODES[v]`, which both destroys the original code and raises `KeyError`
  for a code that isn't in the table — so the day the bank adds one, parsing a
  *successful* payment fails. Here the raw code stays and `resultMessage()` describes
  it, returning `null` when unknown.
- **`KAPITAL_ENV` is read strictly.** An unrecognised value raises rather than warning
  and silently falling back to the test gateway; a warning is easy to miss and the
  consequence is real payments going to the sandbox, or the reverse.
- **Amounts are strings, not floats.** The bank expects the amount as a JSON string,
  and `float` loses precision at the qəpik. Methods accept `int|float|string`; a float
  is formatted so it can never come out in scientific notation.
- **Detail levels are query parameters.** Python embeds them in the endpoint string
  (`...?&tranDetailLevel=2...`, with a stray `&`); here they are passed as `$query`,
  because `Client::uri()` refuses a `?` in a path.
- **Separate request DTOs for the card-registration orders.** `createOrder()` and
  `orderWithSavedCard()` do not declare `aut` at all, matching Python — reusing one DTO
  put `"aut": null` on the wire.
- **`payWithSavedCard()` documents that it is not atomic.** It is three requests; if the
  second or third fails, the created order is left behind for the caller to reverse.
- **No async client.** PHP has no equivalent of the Python package's `*AsyncClient`.

[0.1.0]: https://github.com/integrify-sdk/integrify-php/releases/tag/kapitalbank-0.1.0
