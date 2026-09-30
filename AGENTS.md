# AGENTS.md: CaptchaFox for Joomla

Guide for developers and coding agents working on this repository. The [README](README.md) explains
installation and use for site administrators; this file explains how the code is organised, which
rules apply and how changes and releases are made.

## What this is

`plg_captcha_captchafox`, a Joomla captcha plugin that makes [CaptchaFox](https://captchafox.com)
available as a captcha in Joomla 5.4 and later and in Joomla 6. Published by CaptchaFox (Scoria Labs
GmbH) under GPL-2.0-or-later.

## Layout

```
plugin/                          everything in the installable ZIP, and nothing else
├── captchafox.xml               manifest: version, parameters, update server and changelog URLs
├── script.php                   installer checks: PHP >= 8.1, Joomla >= 5.4.0, Joomla major < 7
├── services/provider.php        service provider, creates the plugin
├── src/Extension/CaptchaFox.php registers the captcha provider (onCaptchaSetup), legacy fallback
├── src/Provider/                widget markup, assets, answer check (CaptchaProviderInterface)
├── src/Verification/            /siteverify client and classification of its responses
├── src/Language/                Joomla language tag -> CaptchaFox language code
├── media/js/captchafox.js       explicit widget rendering and submit handling
└── language/{en-GB,de-DE}/      language files
tests/                           PHPUnit tests
build/                           build.php (ZIP), add-update.php (update server entry), phpstan.sh
updates/                         updates.xml (update server) and changelog.xml, read by Joomla sites
docs/                            images for the README, not part of the ZIP
.github/                         CI, release workflow, Dependabot
```

## Why the code looks the way it does

- **One codebase for Joomla 5.4 and 6.** The plugin uses only the captcha provider API
  (`CaptchaProviderInterface`, `CaptchaRegistry`, `onCaptchaSetup`), which is identical in both.
  `InstallerScriptTrait` is not used because it only exists since Joomla 6.0.
- **`getName()` returns the plugin element `captchafox`.** Joomla looks the provider up by the
  element; with any other name it silently falls back to the legacy captcha path.
- **Legacy fallback rejects instead of letting forms through.** If CaptchaFox is selected but not
  registered as provider (plugin disabled, or its access level excludes the visitor), Joomla calls
  `onDisplay()` and `onCheckAnswer()` on the plugin directly. The plugin then shows "Captcha not
  available" and rejects the form; without this, Joomla would accept the form unchecked.
- **Token:** taken from the captcha field value if a form passes one, otherwise from the POST field
  `cf-captcha-response`.
- **Verification:** only `"success": true` from `/siteverify` passes. If the API cannot be reached
  (network error, timeout, HTTP status >= 300, no valid JSON), the setting `api_unavailable` decides;
  the default blocks. Timeout 5 s, no retry, Joomla's proxy settings apply.
- **Widget:** rendered explicitly by `media/js/captchafox.js`. The container markup is static, without
  inline script or session data, so pages work with Joomla's page cache and CSP nonces.
- **Browser lock:** in inline and popup mode an unsolved form is not sent, and a hint appears at the
  widget. Cancel buttons are exempt. The server-side check stays decisive.

## Commands

Docker is enough, no local PHP is needed. With PHP 8.1+ and Composer installed, the Composer scripts
also run directly.

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
build/phpstan.sh
```

| Command | What it does |
|---|---|
| `composer test` | PHPUnit unit tests |
| `composer cs` / `composer cs-fix` | check / fix the code style (rules of the Joomla core) |
| `composer build` | builds `dist/plg_captcha_captchafox-<version>.zip` from `plugin/`, reproducibly |
| `build/phpstan.sh` | PHPStan level 8 for PHP 8.1 against Joomla 5.4 and 6, using the official Joomla Docker images; `docker pull` them first to analyse against the newest patch release |

## Rules

- Code must run on **PHP 8.1** and on **Joomla 5.4 and 6**. PHPStan checks both.
- Code style of the Joomla core, checked by `composer cs`.
- Code, comments and the README are in English. Every user-facing text is a language string in
  `en-GB` and `de-DE`.
- Comments explain the reason as a plain statement, as in the section above.
- Only `plugin/` ends up in the ZIP. Build and development files stay outside of it.
- **Never change the update server URL** in the manifest, and therefore never the repository name,
  the branch `main` or the path `updates/`. Installed sites read updates from there.
- No secrets in the repository. The CaptchaFox test keys (`sk_1111…`, `ok_1111…`, `sk_FFFF…`,
  `ok_FFFF…`) are public and may appear in tests.
- Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/)
  (`feat:`, `fix:`, `docs:`, `test:`, `build:`, `ci:`, `chore:`). Coding agents add no
  `Co-authored-by` or other attribution trailers.
- Pull requests are merged with a **merge commit**, never squashed or rebased, so that the commits
  that were tested stay on `main`.

## Tests

- **Unit tests** cover the classification of `/siteverify` responses and the language mapping. CI
  runs them on PHP 8.1 for every push to `main` and every pull request, and builds the package.
- **End-to-end tests** against real Joomla 5.4 and 6 installations (Playwright, Docker, CaptchaFox
  test keys) are run by the maintainers before every release. A pull request that changes behaviour
  should describe how to reproduce it in a Joomla site.

## Releases

Versions follow [SemVer](https://semver.org/): `fix` → patch, `feat` → minor, raised Joomla or PHP
minimum or removed options → major.

1. On `main`: the version in `plugin/captchafox.xml` and a matching entry in `updates/changelog.xml`.
2. The end-to-end tests pass on exactly that commit.
3. Tag `vX.Y.Z` on that commit and push the tag. The release workflow checks version and changelog,
   builds the ZIP, creates the GitHub release with the ZIP and adds the entry to
   `updates/updates.xml` on `main` (`build/add-update.php`).

Joomla 5.4.9 and 6.1.4 and later show a security level for update entries that carry a
`<security>` element (0 to 4). `build/add-update.php` does not set it yet; add it before the first
security release.
