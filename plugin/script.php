<?php

/**
 * @package     CaptchaFox.Plugin
 * @subpackage  Captcha.CaptchaFox
 *
 * @copyright   (C) 2026 Scoria Labs GmbH
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

/**
 * Installation script: enforces the supported PHP and Joomla versions.
 *
 * Implements InstallerScriptInterface directly because InstallerScriptTrait only exists
 * since Joomla 6.0.
 */
return new class () implements InstallerScriptInterface {
    private const MINIMUM_PHP = '8.1.0';

    private const MINIMUM_JOOMLA = '5.4.0';

    /**
     * First Joomla major version that is not supported. Checked against Version::MAJOR_VERSION
     * because version_compare() would let pre-releases such as 7.0.0-alpha1 through.
     */
    private const UNSUPPORTED_JOOMLA_MAJOR = 7;

    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        $this->loadLanguage($adapter);

        $error = null;

        if (version_compare(PHP_VERSION, self::MINIMUM_PHP, '<')) {
            $error = Text::sprintf('PLG_CAPTCHA_CAPTCHAFOX_INSTALL_PHP_MINIMUM', self::MINIMUM_PHP, PHP_VERSION);
        } elseif (version_compare(JVERSION, self::MINIMUM_JOOMLA, '<')) {
            $error = Text::sprintf('PLG_CAPTCHA_CAPTCHAFOX_INSTALL_JOOMLA_MINIMUM', self::MINIMUM_JOOMLA, JVERSION);
        } elseif (Version::MAJOR_VERSION >= self::UNSUPPORTED_JOOMLA_MAJOR) {
            $error = Text::sprintf('PLG_CAPTCHA_CAPTCHAFOX_INSTALL_JOOMLA_UNSUPPORTED', JVERSION);
        }

        if ($error !== null) {
            Factory::getApplication()->enqueueMessage($error, 'error');

            return false;
        }

        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Loads the plugin's system language file from the installation source, because the
     * plugin is not installed yet during preflight.
     */
    private function loadLanguage(InstallerAdapter $adapter): void
    {
        $source = $adapter->getParent()->getPath('source');

        if ($source) {
            Factory::getApplication()->getLanguage()->load('plg_captcha_captchafox.sys', $source);
        }
    }
};
