<?php

/**
 * @package     CaptchaFox.Plugin
 * @subpackage  Captcha.CaptchaFox
 *
 * @copyright   (C) 2026 Scoria Labs GmbH
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

declare(strict_types=1);

namespace CaptchaFox\Plugin\Captcha\CaptchaFox\Tests\Language;

use CaptchaFox\Plugin\Captcha\CaptchaFox\Language\LanguageMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LanguageMapperTest extends TestCase
{
    #[DataProvider('mappings')]
    public function testFromJoomlaTag(string $tag, ?string $expected): void
    {
        self::assertSame($expected, LanguageMapper::fromJoomlaTag($tag));
    }

    /**
     * @return  array<string, array{string, ?string}>
     */
    public static function mappings(): array
    {
        return [
            'German'                    => ['de-DE', 'de'],
            'Swiss German'              => ['de-CH', 'de'],
            'British English'           => ['en-GB', 'en'],
            'Brazilian Portuguese'      => ['pt-BR', 'pt'],
            'Irish'                     => ['ga-IE', 'ga'],
            'Ukrainian'                 => ['uk-UA', 'uk'],
            'Simplified Chinese'        => ['zh-CN', 'zh-cn'],
            'Traditional Chinese'       => ['zh-TW', 'zh-tw'],
            'Hong Kong Chinese'         => ['zh-HK', 'zh-tw'],
            'other Chinese'             => ['zh-SG', null],
            'Norwegian Bokmål'          => ['nb-NO', 'no'],
            'Norwegian Nynorsk'         => ['nn-NO', 'no'],
            'unsupported: Catalan'      => ['ca-ES', null],
            'unsupported: Greek'        => ['el-GR', null],
            'unsupported: Hungarian'    => ['hu-HU', null],
            'lowercase and underscore'  => ['fr_fr', 'fr'],
            'surrounding whitespace'    => [' it-IT ', 'it'],
            'empty'                     => ['', null],
        ];
    }

    public function testEveryMappedCodeIsSupported(): void
    {
        foreach (self::mappings() as [$tag, $expected]) {
            if ($expected !== null) {
                self::assertTrue(LanguageMapper::isSupported($expected), $tag);
            }
        }
    }

    public function testTwentyTwoLanguagesAreSupported(): void
    {
        self::assertCount(22, LanguageMapper::SUPPORTED);
        self::assertFalse(LanguageMapper::isSupported('xx'));
    }
}
