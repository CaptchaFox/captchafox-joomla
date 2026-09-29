<?php

/**
 * @package     CaptchaFox.Plugin
 * @subpackage  Captcha.CaptchaFox
 *
 * @copyright   (C) 2026 Scoria Labs GmbH
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace CaptchaFox\Plugin\Captcha\CaptchaFox\Provider;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Captcha\CaptchaProviderInterface;
use Joomla\CMS\Form\FormField;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * CaptchaFox captcha provider.
 *
 * Skeleton: renders a static placeholder container and rejects every answer until the
 * widget and the server-side verification are implemented.
 */
final class CaptchaFoxProvider implements CaptchaProviderInterface
{
    /**
     * Provider name. Must equal the plugin element, otherwise Joomla does not find the provider.
     */
    public const NAME = 'captchafox';

    public function __construct(
        private Registry $params,
        private ?CMSApplicationInterface $application
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param   string                $name        Input name of the captcha field.
     * @param   array<string, mixed>  $attributes  Field attributes (id, class).
     *
     * @return  string
     */
    public function display(string $name = '', array $attributes = []): string
    {
        $id = (string) ($attributes['id'] ?? '');

        return '<div class="captchafox" data-captchafox-joomla="placeholder"'
            . ($id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '')
            . '></div>';
    }

    public function checkAnswer(?string $code = null): bool
    {
        return false;
    }

    public function setupField(FormField $field, \SimpleXMLElement $element): void
    {
    }
}
