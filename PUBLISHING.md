# Publishing

How releases of the packages under `packages/*` reach Packagist. Today that is
`integrify/core`; each new integration follows the same path.

If you know PyPI, read [How this differs from PyPI](#how-this-differs-from-pypi) first —
the model is genuinely different and the differences are where mistakes happen.

---

## How this differs from PyPI

| | PyPI (Python) | Packagist (PHP) |
| :--- | :--- | :--- |
| What you upload | a built artefact (`.whl` / `.tar.gz`) | **nothing** — you upload no files, ever |
| Where the code comes from | the uploaded artefact | your Git repository, read directly |
| Where the version comes from | `version` in `pyproject.toml` | the **Git tag** |
| What triggers a release | `twine upload` / `uv publish` | pushing a Git tag |
| Packages per repository | any number | **exactly one** |
| Auth | API token used at upload time | a one-time repo registration; no token at release |

Two consequences worth internalising:

1. **There is no `version` field in `composer.json`.** This is not an oversight. If you
   add one it silently overrides the tag and you get a package that is stuck at that
   version forever. The publish workflow fails the build if it finds one.
2. **One repository can only publish one package.** Packagist reads `composer.json` from
   the *root* of a repository. It has no concept of "the package in `packages/core`".

That second point is why this repo has a split step.

---

## The split

Because Packagist is one-package-per-repo, this monorepo is mirrored into two
**read-only** repositories, each containing a single package with its `composer.json`
at the root. Packagist watches the mirrors, not this repo.

```
integrify-php  (you work here — the only repo you ever commit to)
│
├── packages/core/          ──split──▶  Integrify-SDK/integrify-php-core
│   ├── composer.json                   ├── composer.json   ← now at the root
│   └── src/                            └── src/
│                                            │
│                                            ▼
│                                       packagist.org: integrify/core
│
└── packages/<name>/        ──split──▶  Integrify-SDK/integrify-php-<name>
                                             │
                                             ▼
                                        packagist.org: integrify/<name>
```

CI force-pushes the mirrors. **Never commit to them by hand** — your changes would be
overwritten on the next push to `main`.

This is the standard approach; Symfony, Laravel, Doctrine and Filament all do exactly
this. The alternative — [Private Packagist's multipackage
feature](https://blog.packagist.com/installing-composer-packages-from-monorepos/) — can
publish straight from a monorepo without splitting, but it is a paid product and it
requires every consumer to add your organisation's repository URL to their own
`composer.json` before they can install. For a public library that is a non-starter.

---

## One-time setup

Do this once. It takes about ten minutes.

### 1. Create the two mirror repositories

On GitHub, under the `Integrify-SDK` organisation, create two **empty** repositories
(no README, no licence, no `.gitignore` — completely empty):

- `integrify-php-core`
- one `integrify-php-<name>` per future integration

Give each a description like *"Read-only mirror of integrify-php/packages/core. Do not
commit here."*

> If you want different names, change them in two places in
> `.github/workflows/publish.yml`: the `matrix.package[].mirror` values, and the
> `MIRROR` expression in the `notify` job.

### 2. Create a token so CI can write to the mirrors

The default `GITHUB_TOKEN` in Actions can only write to the repository it runs in, so
pushing to a *different* repo needs a personal access token.

1. GitHub → Settings → Developer settings → **Personal access tokens** → *Tokens
   (classic)* → **Generate new token**.
2. Scope: **`repo`** (full control of private repositories — needed for pushing).
3. Expiry: your call; if it expires, releases fail loudly and you regenerate it.
4. Copy the token.
5. In **this** repo: Settings → Secrets and variables → Actions → **New repository
   secret**, named `SPLIT_TOKEN`, pasting the token as the value.

> Fine-grained tokens also work: grant *Contents: Read and write* on the two mirror
> repositories only. That is tighter, and preferable if you are comfortable with it.

### 3. Do a first split before registering with Packagist

Packagist rejects a repository that has no `composer.json` yet, so populate the mirrors
first. Push anything to `main` (or run the workflow manually):

- GitHub → Actions → **Publish** → *Run workflow* → leave the tag input empty → Run.

Confirm both mirrors now contain `composer.json`, `src/`, `README.md` at their root.

### 4. Register the packages on Packagist

1. Create an account on [packagist.org](https://packagist.org) (or sign in with GitHub).
2. Click **Submit**, paste `https://github.com/Integrify-SDK/integrify-php-core`, submit.
3. Repeat for each future integration mirror.

Packagist reads each mirror's `composer.json` and registers the package under the `name`
field inside it — `integrify/core`, and later one per integration. **The repository name and
the package name are independent**; the mirrors could be called anything.

> The `integrify` vendor prefix is claimed by whoever registers it first. If it is
> already taken by someone else, you will have to pick another vendor name and update the
> `name` field in all three `composer.json` files.

### 5. Turn on automatic updates

So Packagist re-reads the mirror the moment you push a tag:

- On [packagist.org](https://packagist.org), open your profile → **Settings** and connect
  your GitHub account; Packagist installs its webhook on your repositories automatically.
- Or per-repository: mirror repo → Settings → Webhooks → Add webhook, using the URL
  shown on the Packagist package page.

**Optional belt-and-braces.** The workflow can also ping Packagist's API directly if you
set two more secrets in this repo — otherwise it skips that step and relies on the
webhook:

| Secret | Where to find it |
| :--- | :--- |
| `PACKAGIST_USERNAME` | your packagist.org username |
| `PACKAGIST_TOKEN` | packagist.org → Profile → **Show API Token** |

---

## Releasing

Everything below happens on `main` in **this** repo.

### 1. Write the changelog entry

Edit the package's `CHANGELOG.md`, e.g. `packages/core/CHANGELOG.md`:

```markdown
## [0.1.1] - 2026-09-20

### Fixed

- `Response::toArray()` no longer throws on a body that is valid JSON but not an object.

[0.1.1]: https://github.com/Integrify-SDK/integrify-php/releases/tag/core-0.1.1
```

The workflow **fails the release** if it cannot find `[0.1.1]` in that file. This is the
stand-in for Python's "tag version must match `pyproject.toml`" check — since PHP has no
version field to compare against, the changelog is the thing that proves you meant it.

### 2. Merge to main

The tag must point at a commit that is already on `main`; the workflow refuses to publish
from an unmerged commit.

### 3. Tag and push

The tag format is `<package>-<version>`, matching the Python repo:

```console
git tag core-0.1.0
git push origin core-0.1.0
```

| Tag | Publishes |
| :--- | :--- |
| `core-0.1.0` | `integrify/core` 0.1.0 |
| `core-0.1.1` | `integrify/core` 0.1.1 |
| `epoint-0.1.0` | `integrify/epoint` 0.1.0 |

The tag is renamed on the way into the mirror — `core-0.1.0` here becomes plain
`0.1.0` there, because that is what Packagist parses as the version number.

### 4. Watch it go green

GitHub → Actions → **Publish**. In order it will:

1. **resolve** — parse the tag into package + version;
2. **verify** — package directory exists, tag is on `main`, changelog has the entry, no
   `version` field in `composer.json`, every `composer.json` validates, then code style,
   static analysis and the full test suite;
3. **split** — push the package into its mirror and create the `0.1.0` tag there;
4. **notify** — ping Packagist (or leave it to the webhook).

The new version appears on `https://packagist.org/packages/integrify/core` within
about a minute. Verify:

```console
composer show integrify/core --all
```

### Semantic versioning

Composer's caret operator allows changes that do not touch the **left-most non-zero**
digit. That makes `0.x` behave differently from `1.x`, and it is the single most
important thing to understand while the packages are pre-1.0:

```
^1.0  ->  >=1.0.0 <2.0.0     minor and patch updates
^0.1  ->  >=0.1.0 <0.2.0     patch updates ONLY
^0.0.3 -> >=0.0.3 <0.0.4     no updates at all
```

So while a package is `0.x`, the **minor** is the breaking position:

| Change | Bump while `0.x` | Bump once `1.0.0` |
| :--- | :--- | :--- |
| Bug fix, no API change | patch — `0.1.0` → `0.1.1` | patch — `1.0.0` → `1.0.1` |
| New method, new optional parameter | minor — `0.1.0` → `0.2.0` | minor — `1.0.0` → `1.1.0` |
| Renamed/removed method, changed parameter order, new required parameter | minor — `0.1.0` → `0.2.0` | major — `1.0.0` → `2.0.0` |

Note the middle row: adding a method is *not* breaking, but under `^0.1` it still forces
every consumer to widen their constraint, because `0.2.0` falls outside the range. If
that becomes annoying, that is the signal to release `1.0.0`.

Adding a parameter to an existing method's signature is a **breaking** change in PHP if
it is required, and safe if it has a default — the same rule as Python.

### Releasing core

An integration package declares `"integrify/core": "^0.1"`. If a release of core adds
something that package then relies on, release core **first**, then raise the floor in
the integration's `composer.json`, then release the integration. Otherwise someone
installing it can end up with an older core that lacks the new behaviour.

While core is `0.x` this is more disruptive than it sounds: a `0.2.0` core release means
**every** integration pinned at `^0.1` must be re-released with `^0.2` before anyone can
install the new core alongside them. Two ways to soften that:

- keep integrations on a range that spans the churn — `"integrify/core": ">=0.1 <0.3"` —
  and tighten it once core stabilises; or
- publish core as `1.0.0` from the start and treat the integrations as the `0.x` ones.

The second is what most PHP libraries do, and it costs nothing: `1.0.0` is a statement
about the constraint you are offering consumers, not a claim of maturity.

---

## Troubleshooting

**"Tag is not an ancestor of main"** — you tagged a commit that never got merged. Delete
the tag (`git push --delete origin core-0.1.0`), merge, re-tag.

**"CHANGELOG.md has no `[0.1.0]` entry"** — add the entry, amend or move the tag, push again.

**"composer.json declares a version field"** — remove it. Packagist reads the tag.

**Split job fails with 403 / "could not read Username"** — `SPLIT_TOKEN` is missing,
expired, or lacks `repo` scope. Regenerate and update the secret.

**Packagist shows no new version** — open the package page and click **Update**. If the
manual update works, the webhook is not installed (step 5 above). Check the mirror
actually received the tag: it should be listed under its Tags.

**Wrong package published** — the tag prefix must exactly match a directory name under
`packages/`. `cor-0.1.0` (a typo) fails the "package directory exists" check rather than
publishing the wrong thing.

**Deleting a bad release** — you cannot overwrite a version on Packagist. Delete the tag
in the mirror repo (and here), let Packagist re-crawl, then release a new patch version.
Deleting a tag that people may already have installed is disruptive; prefer releasing
`0.1.1` over unpublishing `0.1.0`.
