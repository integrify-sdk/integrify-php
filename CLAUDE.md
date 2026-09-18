# Integrify (PHP) — project guide

Integrify is a family of PHP libraries that wrap Azerbaijani service APIs behind one
small, consistent client abstraction. This repo is a **Composer monorepo**: every
integration is its own distribution under `packages/*`, published independently to
Packagist, all sharing the `Integrify\` namespace root.

It shares design goals and the exact wire protocol with
[`integrify-python`](https://github.com/integrify-sdk/integrify-python), but **it is not
a transliteration of it**. The PHP API is written for PHP: real typed methods, `readonly`
DTOs, constructor injection, exceptions. When the two libraries disagree on shape, PHP
idiom wins — only the bytes on the wire have to match.

**PHP has no async story comparable to Python's, so every client is sync.**

## Layout

```
composer.json             # umbrella metapackage + workspace autoload + scripts
phpunit.xml.dist          # one testsuite per package
phpstan.neon.dist         # level max, src + tests
.php-cs-fixer.dist.php    # PSR-12 + PHP 8.2 migration rules, single quotes
packages/core/            # integrify/core: Client, Transport, Response, the DTO layer
packages/<integration>/   # one distribution per integration
```

Each integration package looks like this — **add every one of these files when creating
a new integration**:

```
packages/<name>/
├── CHANGELOG.md                 # Keep a Changelog format
├── CITATION.cff
├── LICENSE                      # copy from a sibling package
├── README.md                    # config table, request table, examples
├── composer.json                # name = "integrify/<name>", depends on integrify/core
└── src/
    ├── <Name>Client.php         # extends Client; one real method per endpoint
    ├── <Name>Config.php         # readonly value object + ::fromEnvironment()
    ├── Endpoint.php             # backed enum of paths, sprintf templates for parameters
    ├── Exception/               # package-specific exceptions implementing IntegrifyException
    ├── Enum/                    # backed enums, PascalCase cases
    └── Dto/
        ├── Request/             # readonly DTOs sent to the API
        └── Response/            # readonly DTOs hydrated from the API
└── tests/
    ├── Factory.php              # shared sample DTOs and mock payloads
    ├── <Name>RequestTest.php    # what gets sent (RecordingTransport)
    ├── <Name>ResponseTest.php   # what comes back
    └── <Name>ConfigTest.php
```

Then run **one command**:

```
composer sync-packages
```

Nothing else is wired by hand. `bin/packages.php` discovers packages from
`packages/*/composer.json` and is the single source of truth:

| Consumer | How it picks the package up |
| :--- | :--- |
| Root `composer.json` autoload | `composer sync-packages` rewrites it **and dumps the autoloader**; `composer check-packages` fails if either drifts |
| `phpunit.xml.dist` | `<directory>packages/*/tests</directory>` — PHPUnit resolves `*` itself |
| `phpstan.neon.dist` | `paths: [packages, bin]` — the whole directory (PHPStan does **not** support `*` in `paths`) |
| `publish.yml` release target | the release's tag name, `<package>-<version>`, looked up in the same JSON |
| `standalone.yml` matrix | the same JSON |

The convention that makes discovery work — all three must agree:

```
packages/lsim/  ->  integrify/lsim  ->  integrify-sdk/integrify-lsim-php
```

`bin/packages.php --check` enforces it. It compares three things: `packages/` on disk,
the root `composer.json` autoload map, and — when `vendor/` exists — the *generated*
`vendor/composer/autoload_psr4.php`. The last one matters because `composer.json` can be
perfectly correct while the generated map is stale, which reads as "Class not found"
rather than as a configuration problem. To break the convention deliberately, use that
package's own manifest:

```json
"extra": {"integrify": {"mirror": "other-name", "publish": false}}
```

**The package's own `require-dev` must include a PSR-18 client and a PSR-17 factory**
(`guzzlehttp/guzzle`, `nyholm/psr7`). `integrify/core` requires the *virtual* packages
`psr/http-client-implementation` and `psr/http-factory-implementation`, so a standalone
install of anything depending on core cannot resolve without real ones.

The one manual step that remains is outside the repo: **create the mirror repository**
`integrify-<name>-php` on GitHub **with a README** and submit it to Packagist once.
The setup notes live in the header comment of `.github/workflows/publish.yml`.

## Commands

```
composer install         # dependencies
composer packages        # list the packages and their mirrors
composer sync-packages   # rewrite the root autoload map from packages/
composer check-packages  # verify it is in sync (CI runs this)
composer format          # php-cs-fixer fix
composer lint            # php-cs-fixer fix --dry-run --diff
composer type-check      # phpstan analyse (level max)
composer test            # phpunit
composer test -- packages/lsim/tests    # one package
composer coverage        # phpunit with HTML + text coverage
composer all             # check-packages + format + type-check + test
```

Hooks mirror those scripts (`.pre-commit-config.yaml`), so there is one definition of
"what lint means":

```
pre-commit install
pre-commit install --hook-type pre-push   # `test` and `secure` run on push
pre-commit run --all-files
```

Two non-obvious things in that config, both found by running it rather than reading it:

- `check-xml` needs **both** `files: \.xml(\.dist)?$` and `types: [file]`. `identify`
  gives a `.dist` file no tags at all, and the upstream hook declares `types: [xml]`,
  which is ANDed with `files` — so `files` alone matches nothing and the hook silently
  skips `phpunit.xml.dist`, the one file it exists to protect.
- `format` passes `--path-mode=intersection` so the fixer's own finder stays
  authoritative; without it, a staged file outside `packages/` and `bin/` would be
  reformatted because an explicit path overrides the finder.

## Writing a client

**Endpoints are real, typed, documented methods.** There is no magic dispatch, no
`@method` annotation wall, no route registry. `Client` only supplies the plumbing:

```php
/**
 * Bağlamaların qeydiyyata alınması.
 *
 * **POST** `/api/v4/carriers`
 *
 * @param Package ...$packages Qeydiyyata alınacaq bağlamalar (maksimum 200 ədəd).
 *
 * @throws RequestFailed
 */
public function declare(Package ...$packages): OperationResult
{
    return $this->post(Endpoint::Declarations->value, self::listBody($packages))
        ->to(OperationResult::class);
}
```

Rules that keep the surface consistent:

- **Return the concrete type.** `list<GoodsGroup>`, `CustomsOperations`,
  `OperationResult` — never a generic wrapper the caller has to unwrap.
- **Verb-first names.** `listGoodsGroups()`, `deleteDeclaration()`, `sendDepesh()` —
  not `goodsGroupList()` or `removeDeclared()`.
- **Variadics for batch endpoints** (`declare(Package ...$packages)`) so one and many
  read the same.
- **Optional filters are nullable named parameters**, stripped from the payload when null.
- Header-valued parameters are ordinary typed parameters; the method puts them in the
  header itself. Nothing is guessed from argument names.
- `defaultHeaders()` is the hook for per-client headers (auth, language).
- Dynamic path segments go through `$this->uri('/things/{id}', ['id' => $id])`, which
  `rawurlencode`s each value. String concatenation is refused when it would put a `?` or
  `#` in the path. An absolute URL is allowed only on the base URL's host, because
  `defaultHeaders()` carries the API key — override `assertSameHost()` to widen that.

## The DTO layer (packages/core)

`Data` replaces the slice of pydantic this library needs, via a reflection mapper over
promoted constructor properties:

- `#[Field(name:, of:, maxLength:, minLength:, pattern:, min:, max:, minItems:, maxItems:)]`.
- `name` defaults to the property name; both the property name and the API name are
  accepted on input.
- `of` is required for arrays of DTOs or enums — PHP arrays are not generic — and must
  name a `Data` subclass or an enum; anything else raises at describe time, because a
  typo there used to disable element validation silently.
- Union-typed properties try every member; a DTO or enum converter that throws does not
  abort the remaining alternatives.
- Errors are collected and thrown together as one `ValidationFailed`.
- `from()` / `wrap()` / `wrapAll()` hydrate; `toArray(skipNull:, only:)` serialises.
- Metadata is reflected once per class and cached.

**Empty JSON objects.** PHP's `[]` is both an empty list and an empty map, and
`json_encode()` always renders it `[]`. Intent is therefore encoded explicitly: `[]` means
a JSON array, `stdClass` means a JSON object. `Client::objectBody()` converts, and
`Mapper::flatten()` applies it to any nested DTO that serialises to nothing. Never
"fix" this by guessing from context — a filterless search body and an empty batch are
both `[]` in PHP and must go on the wire differently.

**Numbers and enums.** `Mapper::toInt()` is the single entry point for integer coercion;
it rejects fractional values, non-finite floats and anything outside the int range rather
than truncating or saturating. Note the range check compares against the float literals
`±9223372036854775808.0` and **not** `PHP_INT_MAX`, which a float comparison promotes to
2^63 and therefore lets 2^63 itself through.

`Mapper::toEnum()` is the single entry point for enum coercion, and exists because passing
the wrong scalar type to a backed enum's `tryFrom()` raises a `TypeError` under
`strict_types`, which would escape the DTO layer. Route new conversions through these
rather than adding parallel paths.

The mapper is on the hot path for batch endpoints (up to 200 packages per request), so
a few things are deliberate and should not be "simplified" away:

- `PropertyMeta` precomputes `singleType`, `validates` and `nameMatchesProperty` so the
  common case (a plain `?string` with only a name) skips the generic type loop, the
  validation call and the second `array_key_exists()`.
- `hydrate()` builds a **positional** argument list — properties are visited in
  declaration order and every one is always assigned — which is markedly faster than
  spreading named arguments into the constructor.
- `dehydrate()` reads through a single `get_object_vars()` call rather than N dynamic
  property fetches. That call runs outside the DTO's scope, so it only sees `public`
  properties; a `protected`/`private` promoted property falls back to a scope-bound
  closure read (`readHidden()`). **Keep DTO properties `public`** — the fallback exists
  so a non-public one is not silently serialised as `null`, not as an invitation.
- Length constraints check `strlen()` first and only fall back to `mb_strlen()` when the
  byte count cannot settle it. Lengths are **character** counts — the byte check is only
  a fast path, never the answer. Tests cover this.

## The monorepo's own trap

Every package is autoloaded from one root `vendor/`, so a package can use a class it
never declared a dependency on and the tests still pass. Published, that class is not in
the user's `vendor/` and they get `Class "Integrify\Client" not found`.

Two guards, and neither is optional when adding a package:

- `bin/check-imports.php` — run from a package directory after a standalone install, it
  asserts that every `use` in `src/` resolves through that package's own autoloader.
- The `standalone` CI job installs each package alone and runs its tests against the
  `vendor/` its own manifest produced.

## Errors

Everything the library throws implements `Integrify\Exception\IntegrifyException`:

| Exception | When |
| :--- | :--- |
| `ValidationFailed` | DTO validation failed; nothing was sent |
| `InvalidRequest` | The request could not be built: unencoded path, unfilled placeholder, foreign host |
| `RequestFailed` | Network failure, or HTTP >= 400 — carries `->request` and `->response` |
| `MissingConfiguration` | A required environment variable is absent |
| `<Package>\Exception\*` | Domain failures, e.g. `OperationFailed` |

Because methods return concrete types, failure cannot be signalled through the return
value — HTTP-level problems throw. Service-level failures that arrive with HTTP 200 are
modelled in the response DTO instead (`OperationResult::isSuccessful()`), with an opt-in
`throwOnFailure()`.

## Transport

`Transport` is an interface with two implementations: `HttpTransport` (PSR-18/PSR-17,
discovering the app's client when none is injected) and `RecordingTransport`, which
records requests and replays queued responses. **Tests never mock the HTTP client
directly** — they inject `RecordingTransport`. There is no "dry mode" flag; the recording
transport is the supported way to inspect a payload without sending it.

Both implementations honour the same contract, and keeping them in step is load-bearing:

- a status >= 400 or a network failure raises `RequestFailed`, never a returned value, so
  `RecordingTransport` throws when you queue an error-status `Response`;
- a body that cannot be JSON-encoded (`INF`, `NAN`, invalid UTF-8) raises in **both**, so
  that failure mode cannot hide until production;
- an unqueued request raises unless the test passed an explicit fallback response to the
  constructor — a silent HTTP 200 let a "called once" assertion pass on four calls.

`HttpTransport` wraps every foreign exception — PSR-7 factory errors, discovery failures,
JSON encoding failures, and anything a non-conforming PSR-18 middleware throws — so
nothing that is not an `IntegrifyException` escapes the library.

## Conventions

- **Language**: docblocks, comments and docs are written in **Azerbaijani**; CHANGELOG
  entries and this file are in English. Keep it that way.
- **Style**: PSR-12 + `@PHP82Migration`, `declare(strict_types=1)` everywhere, **single
  quotes**, trailing commas in multiline calls. `composer format` before committing.
- `readonly` classes for every DTO and value object; one class per file (PSR-4),
  including test fixtures.
- **PascalCase enum cases** (`DocumentType::PinCode`), per PER-CS 2.0 — not
  SCREAMING_SNAKE.
- Every public class and method gets a docblock; DTO properties are documented with
  `@param` on the constructor. Client methods additionally carry the HTTP verb and path.
- DTO property names are **PHP names, not API names** — `trackingNumber`, not
  `trackinG_NO`. The wire name belongs in `#[Field(name: ...)]` and nowhere else.
- Response DTOs keep enum-ish fields as `string`/`int` and expose the enum separately for
  reference, so a newly added upstream value can't break validation.
- Configuration is an immutable value object injected into the client. No global state,
  no singletons, no ambient environment reads at call time — `fromEnvironment()` is a
  named constructor, not a lookup. The test suite must pass with no environment set.

## Releasing

**Full procedure and one-time setup: the header comment of
[`.github/workflows/publish.yml`](.github/workflows/publish.yml).** Summary:

Packagist reads one `composer.json` per repository, from the repo root, and takes the
version from the Git tag. So (a) `composer.json` must carry **no** `version` field, and
(b) the monorepo cannot publish two packages by itself — `.github/workflows/publish.yml`
subtree-splits `packages/*` into read-only mirror repositories that Packagist watches.

**Publishing a GitHub Release is what ships a version** — the same trigger as
`integrify-python`. The release's tag name says which package and which version:
`<package>-<version>` (e.g. `core-0.1.0`, `epoint-0.1.0`).

Pushing a tag on its own publishes nothing. A tag with no release is just a tag, and a
draft release does not fire either — only `published` does. So you can tag whenever you
like and still decide separately when to ship.

The workflow discovers packages rather than listing them, so a new integration is
publishable the moment its directory exists and its mirror repo is created. Only the
package named in the tag is touched; the other mirrors are left alone, which means a
mirror's `main` moves only on that package's own releases.

Add the `CHANGELOG.md` entry first — the workflow refuses to publish a version the
changelog does not mention, which is this repo's stand-in for Python's "tag matches
`pyproject.toml`" check. The tag is renamed on the way into the mirror
(`core-0.1.0` → `0.1.0`), because the mirror's tag *is* the published version.

Everything is pre-1.0, and Composer's caret treats a `0.x` **minor** as the breaking
position (`^0.1` is `>=0.1.0 <0.2.0`). Adding a method therefore still forces consumers
to widen their constraint.

Changing `packages/core` means a core release plus a dependency floor bump in any package
that relies on the new behaviour.

## Docs

`docs/az/` is an MkDocs + Material site, the same theme and markdown extensions as the
Python repos so the two sites read alike. It is **not** a hand-written API reference:

```
composer docs         # rebuild docs/az/docs/integrations/** from the source
composer check-docs   # fail if the committed pages have drifted (CI, pre-commit, `all`)
cd docs/az && mkdocs serve
```

`mkdocs` must run **from `docs/az/`** — `pymdownx.snippets`' `base_path: ../..` and the
Python repos' `paths: ../../src` both resolve against the working directory, not against
the config file.

`bin/docs.php` reflects over every package `bin/packages.php` discovers and writes one
page per group: client, config, request DTOs, response DTOs, enums, exceptions, and a
catch-all for the rest. Two things are worth knowing about why it exists:

- **mkdocstrings has no PHP handler** (C, Crystal, GitHub Actions, Python, MATLAB,
  TypeScript, VBA and shell — that is the list), so the `::: module.Class` mechanism the
  Python site is built on has no counterpart here.
- Generic PHP tools would document `public ?string $trackingNo` but not that it goes on
  the wire as `trackinG_NO`. That fact lives in `#[Field]` and only reflection sees it —
  `griffe_pydantic` does the same job on the Python side. It is the single most useful
  column in the generated tables, so the generator exists to produce it.

**A page's sidebar label and its browser-tab title are not the same string.** MkDocs has one
`page.title`, and a `nav:` label claims it — so qualifying the tab title by hand would drag the
package name into every sidebar row. Material's `htmltitle` block checks `page.meta.title`
first, so the generator emits front matter (`title: Azericard · Konfiqurasiya`) and leaves the
nav label short. Without it, seven packages contribute seven pages called "Konfiqurasiya" and
open tabs, bookmarks and search results can't be told apart.

Package display names (`Kapital Bank`, `EPoint`) are parsed from each README's H1, so there is
no second list of names to drift. `navigation.indexes` makes a package's README *be* its
sidebar section instead of a child of it.

A package's **index page is its README**, pulled in with `--8<-- "packages/<name>/README.md"`.
There is no second copy to keep current. Two consequences: a README link has to be an
absolute URL, because it is rendered from a different directory; and `check_paths: true`
is set so a missing README fails the build instead of rendering an empty page.

Docblock links like `` [`Foo`](Foo.php) `` are rewritten to the right page anchor when
`Foo` is documented, and reduced to plain text when it is not — a relative link to a
`.php` file is correct in an IDE and broken on a website, and `mkdocs --strict` refuses
to publish either mistake.

### Publishing

The site is on **Netlify** at <https://integrify-php.mmzeynalli.dev>, built from `main` by
the root `netlify.toml`. That file sets `base = "docs/az"`, which is how the
working-directory rule above is satisfied on the build machine; every other path in it is
relative to that base, so `publish = "site"` means `docs/az/site`. Netlify installs
`docs/az/requirements.txt` on its own. Every PR also gets its own deploy preview URL.

`.github/workflows/docs.yml` does **not** publish anything. It runs `check-docs` and the
same strict build, because a Netlify failure leaves the previous site up without marking
the commit red, and because generated markdown is committed — nothing else would notice
if someone edited a docblock and skipped `composer docs`.

Changing the domain means changing `site_url` in `mkdocs.yml` as well; it feeds the
canonical tags and `sitemap.xml`, which are wrong silently rather than loudly.

## Onboarding

`PHP-PRIMER.md` explains this codebase for contributors who know Python but not PHP.
If you change the architecture, keep its "guided tour" section accurate.
