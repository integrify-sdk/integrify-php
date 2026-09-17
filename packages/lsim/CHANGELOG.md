# Changelog

All notable changes to `integrify/lsim` are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/) and this project follows
[Semantic Versioning](https://semver.org/).

> **Pre-1.0.** Under Composer's caret rules a `0.x` minor is the breaking position:
> `^0.1` resolves to `>=0.1.0 <0.2.0`.

## [0.1.0] - 2026-09-17

First release. Ported from
[`integrify-lsim`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/lsim)
(Python), same wire protocol, PHP-shaped API.

### Added

- `SingleSmsClient` (`https://apps.lsim.az`) — `sendSms()`, `scheduleSms()`,
  `checkBalance()`, `checkStatus()`, `fetchDeliveryReport()`.
- `BulkSmsClient` (`https://www.sendsms.az`) — `sendSameMessage()`,
  `sendDifferentMessages()`, `fetchReport()`, `fetchDetailedReport()`,
  `fetchDetailedReportWithDates()`, `checkBalance()`.
- `LsimConfig` — immutable credentials value object with `fromEnvironment()`. The
  password never reaches a payload on the single-SMS API; it only feeds the `key`
  signature.
- `SmsCode`, `BulkCode` and `SmsStatus` enums, exposed through `code()` / `smsStatus()`
  accessors rather than used as DTO property types, so an unrecognised upstream value
  cannot break validation.

### Differences from the Python package

- **Two clients instead of one namespace.** The two LSIM APIs live on different hosts
  and share no endpoint, so they are separate classes with separate base URLs.
- **`sendDifferentMessages()` refuses mismatched lists.** Python pairs numbers and
  messages with `zip()`, which silently drops the extra items — a message going to
  nobody is a bug you cannot see.
- **`checkStatus()` returns `ReportStatus`, not a DTO.** That endpoint answers with a
  bare integer rather than JSON, and a non-numeric body yields a `null` code instead of
  raising.
- **A malformed bulk envelope raises `ValidationFailed`.** Defaulting `responsecode` to
  `0` would be reported as success, since `0` is LSIM's success code.
- **No async client.** PHP has no equivalent of the Python package's `*AsyncClient`.

[0.1.0]: https://github.com/integrify-sdk/integrify-php/releases/tag/lsim-0.1.0
