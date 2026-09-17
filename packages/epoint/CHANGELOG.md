# Changelog

All notable changes to `integrify/epoint` are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/) and this project follows
[Semantic Versioning](https://semver.org/).

> **Pre-1.0.** Under Composer's caret rules a `0.x` minor is the breaking position:
> `^0.1` resolves to `>=0.1.0 <0.2.0`.

## [0.1.0] - 2026-09-17

First release. Ported from
[`integrify-epoint`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/epoint)
(Python), same wire protocol, PHP-shaped API.

Every one of the ten payloads was diffed field-for-field against the ones the Python
package's pydantic models produce for the same arguments; all eleven cases (including
full and partial refund) are identical.

### Added

- `EPointClient` — `pay()`, `getTransactionStatus()`, `saveCard()`,
  `payWithSavedCard()`, `payAndSaveCard()`, `payout()`, `refund()`, `splitPay()`,
  `splitPayWithSavedCard()`, `splitPayAndSaveCard()`.
- `EPointConfig` — immutable credentials value object with `fromEnvironment()`. The
  private key never reaches a payload; it only feeds the `signature`.
- `Callback` — decodes and verifies the callback EPoint posts to your own URL.
- `TransactionStatus` and `TransactionStatusExtended` enums, plus `BankCode` for the
  bank's 3-character response codes, exposed through `status()` / `codeMessage()`
  accessors rather than used as DTO property types, so an unrecognised upstream value
  cannot break validation.

### Differences from the Python package

- **A bad callback signature raises `SignatureMismatch`.** Python's
  `decode_callback_data()` returns `None`, which is easy to forget to check — and a
  forgotten check means accepting a forged "payment succeeded" callback from anyone who
  can reach your URL. Signature comparison also uses `hash_equals()`, so it does not
  leak the correct prefix length through timing.
- **`code` keeps its raw value.** Python's validator replaces the bank code with its
  description, so `'000'` is no longer comparable; here `$code` stays as sent and
  `codeMessage()` provides the description.
- **Amounts are strings, not floats.** EPoint expects the amount as a JSON string, and
  `float` loses precision at the qəpik (`10.55` → `10.550000000000001`). Methods accept
  `int|float|string`; a float is formatted so it can never come out in scientific
  notation, which EPoint rejects.
- **`getTransactionStatus()` separates the two questions.** `isSuccessful()` answers
  "did the request get through" (the Python `ok` semantics — only `server_error` is a
  failure), and `isPaid()` answers "did the transaction succeed". Conflating them reads
  a failed payment as a successful one.
- **Empty `other_attr` is sent as `{}`.** PHP's `[]` encodes to a JSON array, which the
  API rejects where it expects an object.
- **Separate request DTOs for the card-registration endpoints.** `payAndSaveCard()` and
  `splitPayAndSaveCard()` do not declare `other_attr` at all, matching Python — reusing
  the plain payment DTO would have put `"other_attr": null` on the wire.
- **No async client.** PHP has no equivalent of the Python package's `*AsyncClient`.

[0.1.0]: https://github.com/integrify-sdk/integrify-php/releases/tag/epoint-0.1.0
