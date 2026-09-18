# Changelog

All notable changes to `integrify/postaguvercini` are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/) and this project follows
[Semantic Versioning](https://semver.org/).

> **Pre-1.0.** Under Composer's caret rules a `0.x` minor is the breaking position:
> `^0.1` resolves to `>=0.1.0 <0.2.0`.

## [0.1.0] - 2026-09-18

First release. Ported from
[`integrify-postaguvercini`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/postaguvercini)
(Python), same wire protocol, PHP-shaped API.

All five request payloads were diffed field-for-field against the ones the Python
package's pydantic models produce for the same arguments; every one is identical.

### Added

- `PostaGuverciniClient` — `sendSms()`, `sendMessages()`, `getStatus()`,
  `checkBalance()`.
- `PostaGuverciniConfig` — immutable credentials value object with `fromEnvironment()`.
- `Channel` and `StatusCode` enums, exposed through `status()` accessors rather than
  used as DTO property types, so an unrecognised upstream value cannot break validation.
- `SmsMessage` — a receiver/message pair for the per-recipient endpoint.

### Differences from the Python package

- **`StatusCode` has 22 cases, not 26.** Python declares `ALPHANUMBERIC_QUERY_*` with
  the same 7020–7050 values as `CREDIT_INQUIRY_*`; Python silently turns the repeats
  into aliases, but a duplicate value is a **fatal error** in a PHP backed enum. The
  canonical names are kept and the aliases noted in comments.
- **A misformatted date is refused.** Python's `timestamp_to_str()` returns `None` for
  a string that doesn't match `%Y%m%d %H:%M`, so a typo in a scheduled send turns into
  "send now" — and the pydantic error that follows never mentions the format. Here the
  format is checked and `InvalidRequest` names it. Methods also accept a
  `DateTimeInterface` so it need not be formatted by hand.
- **The date format is `YYYYMMDD HH:MM`.** Python's `types.py` documents it as
  "YYYY-mm-DD HH:MM:SS"; the code disagrees with its own docstring, and the code is
  what the service sees.
- **Per-recipient sending takes `SmsMessage` objects.** The receiver and its text stay
  together, so two lists of different lengths cannot silently drop a message.
- **Empty lists are refused before the request is built.** The service answers an empty
  receiver list with `EmptyReceiverList` (1060); failing locally is the same outcome
  without the round trip.
- **`messages()` / `statuses()` never return `null`.** The service sends `Result: null`
  on failure; these accessors give an empty list so a `foreach` is always safe, while
  `$result` keeps the raw value.
- **`balance()` distinguishes 0 from unknown.** A failed balance request returns `null`
  rather than `0`, which would read as "out of credit".
- **`originator` can live in the config.** Python only takes it per call; the sender
  name is almost always fixed per account, and the argument still overrides it.
- **No async client.** PHP has no equivalent of the Python package's `*AsyncClient`.

### Security note

This service authenticates with `Username` and `Password` **fields in every request
body** — not a header, not a signature, and not hashed as LSIM does. Request bodies for
this integration must not be logged.

[0.1.0]: https://github.com/integrify-sdk/integrify-php/releases/tag/postaguvercini-0.1.0
