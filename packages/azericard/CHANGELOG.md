# Changelog

All notable changes to `integrify/azericard` are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/) and this project follows
[Semantic Versioning](https://semver.org/).

> **Pre-1.0.** Under Composer's caret rules a `0.x` minor is the breaking position:
> `^0.1` resolves to `>=0.1.0 <0.2.0`.

## [0.1.0] - 2026-09-18

First release. Ported from
[`integrify-azericard`](https://github.com/integrify-sdk/integrify-python/tree/main/packages/azericard)
(Python), same wire protocol, PHP-shaped API.

All seven request payloads — **including their RSA and MD5 signatures** — were produced
with the same key and arguments as the Python package's pydantic models and compared
byte for byte; every one is identical.

### Added

- `AzericardClient` — `authorize()`, `authorizeAndSaveCard()`,
  `authorizeWithSavedCard()`, `finalize()`, `startTransfer()` (all of which build a
  browser form and send nothing), plus `getTransactionStatus()`, `confirmTransfer()`
  and `declineTransfer()` (which do POST).
- `FormPost` — the url, method and fields a browser must post, with `toHtml()` for the
  self-submitting form and `toArray()` for your own template.
- `Callback` — reads both callbacks, verifying the transfer signature.
- `Signature` — the two signing schemes, with the MAC source exposed separately so
  tests can pin *what* is signed independently of the key.
- `AzericardConfig` — immutable credentials value object with `fromEnvironment()`, and
  the MPI/MT base URLs derived from `Environment`.
- `AuthorizationType`, `ResponseType`, `Action`, `CardStatus` and `TransferStatusCode`
  enums, exposed through accessors rather than used as DTO property types.

### Differences from the Python package

- **The RSA key and the transfer key are separate settings.** Python reads both from
  `AZERICARD_KEY_FILE_PATH`, but one file cannot be both a PKCS#1 private key and the
  MT shared secret. Here they are `privateKey` and `transferKey`, and only the one your
  flow needs has to be set — the error names the missing variable when it is needed.
- **Signature verification is real code, not `assert`.** Python validates transfer
  signatures with `assert`, which `python -O` strips entirely — under optimisation the
  check silently disappears and a forged response is accepted. Comparison also uses
  `hash_equals()`.
- **Timestamps are UTC.** Azericard documents the field as GMT; Python sends
  `datetime.now()`, the server's local time. In Baku that is four hours out.
- **`assertSameHost()` is widened, not disabled.** Azericard spans two hosts, so the
  client allows exactly its own MPI and MT hosts for the configured environment and
  still refuses anything else.
- **Form values are escaped on render.** Carried over from Python's `json_to_html_form`,
  which escapes with `html.escape(..., quote=True)`; `FormPost::toHtml()` uses
  `htmlspecialchars()` with `ENT_QUOTES | ENT_SUBSTITUTE`.
- **`null` fields are dropped from the form.** Python serialises them as `None` and
  httpx omits them; an HTML form would otherwise render `value=""`.
- **No async client.** PHP has no equivalent of the Python package's `*AsyncClient`.

### Security notes

- **Most of this API is not a server-to-server call.** Five of the eight operations
  return a form for the *browser* to post; the result arrives later at your callback URL.
- **The card callback's `P_SIGN` cannot be verified.** It is signed with Azericard's key,
  and verifying it needs their public key, not your private one. Python does not verify
  it either. Treat the callback as a notification and confirm with
  `getTransactionStatus()` before treating a payment as settled.
- **The transfer signature is a secret-suffix MD5.** That is the scheme Azericard
  specifies; it is not a choice this package makes.

[0.1.0]: https://github.com/integrify-sdk/integrify-php/releases/tag/azericard-0.1.0
