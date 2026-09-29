# CaptchaFox for Joomla

Official [CaptchaFox](https://captchafox.com) captcha plugin for Joomla.

> **Status:** in development, no release yet.

## Compatibility

| Joomla | PHP |
|---|---|
| 5.4 or later | 8.1 or later |
| 6.x | 8.3 or later |

Joomla 3, 4, 5.0–5.3 and 7 are not supported.

## Development

The installable package is built from `plugin/`, which contains exactly the files that end up in
the ZIP. Requirements: PHP 8.1+ with the `zip` extension and Composer.

```bash
composer build
```

This writes `dist/plg_captcha_captchafox-<version>.zip`. The version is taken from
`plugin/captchafox.xml`.

Without a local PHP installation, the build can run in the official Composer image:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
```

## License

Copyright (C) 2026 Scoria Labs GmbH. Licensed under the GNU General Public License version 2 or
later (GPL-2.0-or-later), see [LICENSE](LICENSE).
