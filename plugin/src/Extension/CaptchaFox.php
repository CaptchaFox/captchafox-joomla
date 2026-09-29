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
use CaptchaFox\Plugin\Captcha\CaptchaFox\Verification\SiteVerifyClient;
use Joomla\CMS\Event\Captcha\CaptchaSetupEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Http\HttpFactory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Registers the CaptchaFox captcha provider with Joomla's captcha registry.
 */
final class CaptchaFox extends CMSPlugin implements SubscriberInterface
{
    /**
     * @param   array<string, mixed>  $config       Plugin configuration (name, type, params).
     * @param   HttpFactory           $httpFactory  Creates the client for the siteverify request.
     */
    public function __construct(array $config, private HttpFactory $httpFactory)
    {
        parent::__construct($config);
    }

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
        // Loaded here instead of $autoloadLanguage: the plugin only needs its strings when a captcha is used.
        $this->loadLanguage();
        $this->addLogger();

        $event->getCaptchaRegistry()->add(
            new CaptchaFoxProvider(
                $this->params,
                $this->getApplication(),
                new SiteVerifyClient($this->httpFactory, $this->httpOptions())
            )
        );
    }

    /**
     * Fallback when Joomla does not find the provider.
     *
     * If CaptchaFox is still selected as captcha but missing from the captcha registry (plugin
     * disabled, or its access level excludes the visitor), Joomla boots the plugin anyway and calls
     * the legacy captcha methods directly on it. Without them Joomla would accept the form unchecked.
     * With them the form is rejected and a notice is shown where Joomla renders the field.
     *
     * Joomla logs a deprecation notice in that case. Joomla 7 removes this legacy path.
     *
     * @param   string|null  $name   Input name of the captcha field.
     * @param   string|null  $id     Id of the captcha field.
     * @param   string       $class  CSS class of the captcha field.
     *
     * @return  string
     */
    public function onDisplay($name = null, $id = null, $class = ''): string
    {
        // Booted without its plugin record, the plugin has not loaded its language yet.
        $this->loadLanguage();

        return '<div class="alert alert-warning captchafox-joomla-unavailable">'
            . Text::_('PLG_CAPTCHA_CAPTCHAFOX_UNAVAILABLE')
            . '</div>';
    }

    /**
     * Fallback when Joomla does not find the provider: always reject (see onDisplay()).
     *
     * @param   string|null  $code  The submitted answer (unused).
     *
     * @return  bool
     */
    public function onCheckAnswer($code = null): bool
    {
        return false;
    }

    /**
     * Writes the plugin's log entries to their own file. Rejected answers are logged at DEBUG level
     * and only kept when Joomla's debug mode is on, so bot traffic does not fill the log.
     */
    private function addLogger(): void
    {
        $priorities = (\defined('JDEBUG') && JDEBUG) ? Log::ALL : Log::ALL & ~Log::DEBUG;

        Log::addLogger(
            ['text_file' => CaptchaFoxProvider::LOG_CATEGORY . '.php'],
            $priorities,
            [CaptchaFoxProvider::LOG_CATEGORY]
        );
    }

    /**
     * HTTP client options. The framework client does not read Joomla's global proxy settings by
     * itself, so they are passed to the curl transport here.
     *
     * @return  array<string, mixed>
     */
    private function httpOptions(): array
    {
        $options = ['userAgent' => 'CaptchaFox-Joomla'];
        $app     = $this->getApplication();

        if ($app === null || !$app->get('proxy_enable') || !\defined('CURLOPT_PROXY')) {
            return $options;
        }

        $host = trim((string) $app->get('proxy_host', ''));

        if ($host === '') {
            return $options;
        }

        $port = trim((string) $app->get('proxy_port', ''));
        $curl = [CURLOPT_PROXY => $port !== '' ? $host . ':' . $port : $host];
        $user = (string) $app->get('proxy_user', '');

        if ($user !== '') {
            $curl[CURLOPT_PROXYUSERPWD] = $user . ':' . (string) $app->get('proxy_pass', '');
        }

        $options['transport.curl'] = $curl;

        return $options;
    }
}
