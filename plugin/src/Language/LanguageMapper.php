<?php

/**
 * @package     CaptchaFox.Plugin
 * @subpackage  Captcha.CaptchaFox
 *
 * @copyright   (C) 2026 Scoria Labs GmbH
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace CaptchaFox\Plugin\Captcha\CaptchaFox\Language;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Maps Joomla language tags (de-DE, zh-TW, …) to CaptchaFox widget language codes.
 *
 * Without an explicit language the widget follows the browser language, not the page language.
 * Explicit codes are not normalised by the widget, so the mapping must produce its exact codes.
 */
final class LanguageMapper
{
    /**
     * Language codes supported by the CaptchaFox widget (docs.captchafox.com/en/language-codes).
     */
    public const SUPPORTED = [
        'cs', 'da', 'de', 'en', 'es', 'fi', 'fr', 'ga', 'id', 'it', 'ja',
        'ko', 'nl', 'no', 'pl', 'pt', 'ru', 'sv', 'tr', 'uk', 'zh-cn', 'zh-tw',
    ];

    /**
     * Tags whose code differs from their primary subtag.
     */
    private const SPECIAL = [
        'zh-cn' => 'zh-cn',
        'zh-tw' => 'zh-tw',
        'zh-hk' => 'zh-tw',
        'nb-no' => 'no',
        'nn-no' => 'no',
    ];

    /**
     * @return  string|null  The CaptchaFox code, or null if the language is not supported (the
     *                       widget then falls back to the browser language).
     */
    public static function fromJoomlaTag(string $tag): ?string
    {
        $tag = strtolower(str_replace('_', '-', trim($tag)));

        if (isset(self::SPECIAL[$tag])) {
            return self::SPECIAL[$tag];
        }

        $primary = explode('-', $tag)[0];

        // Other Chinese variants are ambiguous between simplified and traditional script.
        if ($primary === 'zh') {
            return null;
        }

        return self::isSupported($primary) ? $primary : null;
    }

    public static function isSupported(string $code): bool
    {
        return \in_array($code, self::SUPPORTED, true);
    }
}
