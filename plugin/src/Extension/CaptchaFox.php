<?php

/**
 * @package     CaptchaFox.Plugin
 * @subpackage  Captcha.CaptchaFox
 *
 * @copyright   (C) 2026 Scoria Labs GmbH
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace CaptchaFox\Plugin\Captcha\CaptchaFox\Extension;

use CaptchaFox\Plugin\Captcha\CaptchaFox\Provider\CaptchaFoxProvider;
use Joomla\CMS\Event\Captcha\CaptchaSetupEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Registers the CaptchaFox captcha provider with Joomla's captcha registry.
 */
final class CaptchaFox extends CMSPlugin implements SubscriberInterface
{
    /**
     * @return  array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onCaptchaSetup' => 'registerProvider',
        ];
    }

    /**
     * @param   CaptchaSetupEvent  $event  The captcha setup event.
     *
     * @return  void
     */
    public function registerProvider(CaptchaSetupEvent $event): void
    {
        $event->getCaptchaRegistry()->add(
            new CaptchaFoxProvider($this->params, $this->getApplication())
        );
    }
}
