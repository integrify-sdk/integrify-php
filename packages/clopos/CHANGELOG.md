# Changelog

All notable changes to `integrify/clopos` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-09-18

### Added

- `CloposClient` covering all 27 endpoints of the Clopos Open API v2: auth, venues,
  users, customers, customer groups, categories, stations, products, stop list, sale
  types, payment methods, orders, receipts and price lists.
- `CloposConfig` with `fromEnvironment()`, reading `CLOPOS_CLIENT_ID`,
  `CLOPOS_CLIENT_SECRET`, `CLOPOS_BRAND`, `CLOPOS_INTEGRATOR_ID` and `CLOPOS_VENUE_ID`.
- The token is held by the client and sent as `x-token` on every request, instead of
  being passed by hand on each call as the Python library requires.
- `Page`, a generic paginated result that iterates as a list and also carries `total`.
- `RequestRejected`, which reads the service's structured `error[]` body so a failure
  cannot be ignored.
- Typed filter objects (`CustomerFilter`, `StopListFilter`, `ProductFilter`) and enums
  for every documented literal set.

### Fixed — differences from `integrify-clopos` (Python)

- **`listProducts()` sends real query parameters.** The Python handler runs the whole
  payload through `json.dumps()` before handing it to httpx as `params`, which puts one
  JSON blob in the query string instead of the filters; no filter reaches the service.
- **Customer filter values use `filters[i][1]`.** Python writes the key as
  `filter[i][1]` — singular — so the value half of every customer filter is dropped.
- **`listCustomers()` sends its pagination.** The Python request model's
  `@model_serializer` replaces the whole dump with the `with`/`filter` keys, so `page`
  and `limit` never reach the service.
- **`getOrder(with:)` sends `with`.** Python sends `with_`, the Python-side property
  name, because that request model declares no serialization alias.
