<?php

/**
 * @package     CaptchaFox.Plugin
 * @subpackage  Captcha.CaptchaFox
 *
 * @copyright   (C) 2026 Scoria Labs GmbH
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

\defined('_JEXEC') or die;

use CaptchaFox\Plugin\Captcha\CaptchaFox\Extension\CaptchaFox;
use CaptchaFox\Plugin\Captcha\CaptchaFox\Provider\CaptchaFoxProvider;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Http\HttpFactory;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the plugin with the DI container.
     *
     * Name, type and empty params are passed explicitly: when the provider is missing from the
     * captcha registry, Joomla still boots the plugin, but without its plugin record.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $config = array_merge(
                    [
                        'name'   => CaptchaFoxProvider::NAME,
                        'type'   => 'captcha',
                        'params' => '{}',
                    ],
                    (array) PluginHelper::getPlugin('captcha', CaptchaFoxProvider::NAME)
                );

                $plugin = new CaptchaFox($config, new HttpFactory());
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
