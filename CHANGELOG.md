# Changelog

All notable changes to the Integrify PHP monorepo are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/) and this project follows
[Semantic Versioning](https://semver.org/).

Each package under `packages/*` keeps its own `CHANGELOG.md` and is released
independently; this file records repository-wide changes.

## [0.1.0] - 2026-09-17

### Added

- Initial repository layout: a Composer monorepo with `integrify/core`, PHPUnit +
  PHPStan (`level: max`) + PHP-CS-Fixer tooling, and a GitHub Actions matrix over
  PHP 8.2 / 8.3 / 8.4.
- Tag-driven publishing (`.github/workflows/publish.yml`): `<package>-<version>`
  subtree-splits `packages/*` into read-only mirror repositories that Packagist watches.
  See [`PUBLISHING.md`](PUBLISHING.md).
