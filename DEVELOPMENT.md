# Development

## Requirements

PHP 8.1 or later with the `zip` extension and Composer, or only Docker:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
```

Every `composer` command below runs the same way in the Composer image.

## Build

```bash
composer build
```

writes `dist/plg_captcha_captchafox-<version>.zip`, reproducibly; the version is taken from
`plugin/captchafox.xml`. Only `plugin/` ends up in the ZIP:

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

## Test

```bash
composer test
```

runs the unit tests (PHPUnit). They cover the classification of `/siteverify` responses and the
language mapping; CI runs them for every push to `main` and every pull request and builds the package.
End-to-end tests against Joomla 5.4 and 6 run before every release.

## Checks

| Command | Checks |
|---|---|
| `composer cs` | Code style, the rules of the Joomla core (`composer cs-fix` fixes it) |
| `build/phpstan.sh` | Static analysis (PHPStan level 8, PHP 8.1) against Joomla 5.4 and 6, using the Joomla source in the official Docker images (`docker pull` them first to analyse against the newest patch release) |
