# Changelog

All notable changes to the Integrify PHP monorepo are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/) and this project follows
[Semantic Versioning](https://semver.org/).

Each package under `packages/*` keeps its own `CHANGELOG.md` and is released
independently; this file records repository-wide changes.

## [0.1.0] - 2026-09-17

### Added

- Initial repository layout: a Composer monorepo with `integrify/core` and
  `integrify/lsim`, PHPUnit +
  PHPStan (`level: max`) + PHP-CS-Fixer tooling, and a GitHub Actions matrix over
  PHP 8.2 / 8.3 / 8.4.
- Tag-driven publishing (`.github/workflows/publish.yml`): `<package>-<version>`
  subtree-splits `packages/*` into read-only mirror repositories that Packagist watches.
  See [`PUBLISHING.md`](PUBLISHING.md).
- `.github/workflows/standalone.yml` — installs each package **alone**, with only its own
  `composer.json` filling `vendor/`, and runs its tests against that autoloader. The
  monorepo's shared root autoloader otherwise hides a package using a class it never
  declared a dependency on, which only fails once someone installs it.
- `bin/check-imports.php` — the direct form of the same check: every `use` statement in
  `src/` must resolve through the package's own autoloader.
- `.pre-commit-config.yaml` — the same pre-commit setup as `integrify-python`, with every
  PHP hook delegating to a composer script so the hooks cannot drift from CI. Markup
  checks (XML/YAML/JSON) run on commit; `test` and `secure` run on push.
- `bin/packages.php` — package discovery, and the single source of truth for the CI
  matrices, mirror names and the root autoload map. Adding an integration no longer means
  editing five files: create the directory and run `composer sync-packages`.
  `composer check-packages` fails CI if the root autoload map drifts, and also if
  `vendor/composer/autoload_psr4.php` is stale — adding a package without running
  `composer dump-autoload` otherwise surfaces as dozens of "Class not found" errors
  from PHPUnit with nothing actually misconfigured.
