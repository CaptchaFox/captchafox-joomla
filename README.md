# CaptchaFox for Joomla

Official [CaptchaFox](https://captchafox.com) captcha plugin for Joomla.

> **Status:** in development, no release yet.

## Compatibility

| Joomla | PHP |
|---|---|
| 5.4 or later | 8.1 or later |
| 6.x | 8.3 or later |

Joomla 3, 4, 5.0–5.3 and 7 are not supported.

## Content Security Policy

If your site sends a Content Security Policy, for example with the core plugin
"System - HTTP Headers", allow CaptchaFox in these directives. Keep your existing values such as
`'self'`, which the plugin's own script needs.

| Directive | Add |
|---|---|
| `script-src` | `https://*.captchafox.com blob:` |
| `connect-src` | `https://*.captchafox.com` |
| `style-src` | `https://*.captchafox.com` |
| `img-src` | `https://*.captchafox.com` |
| `media-src` | `https://*.captchafox.com` |

- If you set `worker-src` or `child-src`, add `blob:` there as well. The widget runs a web worker from
  a `blob:` URL.
- With "Nonce" enabled in "System - HTTP Headers", Joomla adds the nonce to the plugin's scripts
  automatically.
- Subresource Integrity (SRI) is not possible: CaptchaFox serves `api.js` from an unversioned URL, and
  the file must be loaded from the CaptchaFox CDN.

## Troubleshooting

### Scripts are blocked on cached pages when a CSP nonce is used

**Symptom:** With "System - Page Cache" and a Content Security Policy with "Nonce" enabled, the
widget or other scripts do not load for some visitors. The browser console reports Content Security
Policy violations.

**Cause:** This is a general Joomla limitation, not specific to CaptchaFox. The page cache stores the
complete page, including the nonce of the visit that filled the cache. Later visitors get a new nonce
in the CSP header, so every script that relies on the nonce alone is blocked.

**CaptchaFox is not affected** as long as your policy allows `https://*.captchafox.com` (see above),
`'self'` is allowed in `script-src`, and "strict-dynamic" is disabled: the browser then accepts these
scripts by their origin. With "strict-dynamic" enabled, the browser ignores origins and relies on the
nonce only.

**Solutions:**

- Exclude pages with forms from the page cache ("System - Page Cache", options "Exclude Menu Items" or
  "Exclude URLs"), or
- do not combine the page cache with a nonce-based policy that uses "strict-dynamic".

### Content Security Policy reports inline styles of the widget

Without `'unsafe-inline'` in `style-src`, the browser may report blocked inline style attributes
(`style-src-attr`) of the widget. These reports are harmless; the widget keeps working.

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
