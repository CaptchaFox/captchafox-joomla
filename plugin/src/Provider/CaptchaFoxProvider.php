<?php

/**
 * @package     CaptchaFox.Plugin
 * @subpackage  Captcha.CaptchaFox
 *
 * @copyright   (C) 2026 Scoria Labs GmbH
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace CaptchaFox\Plugin\Captcha\CaptchaFox\Provider;

use CaptchaFox\Plugin\Captcha\CaptchaFox\Verification\SiteVerifyClient;
use CaptchaFox\Plugin\Captcha\CaptchaFox\Verification\VerificationResult;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Captcha\CaptchaProviderInterface;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * CaptchaFox captcha provider.
 *
 * Renders a static container that the init script turns into a CaptchaFox widget. The markup
 * contains no inline script and no session data, so it works with page caching and nonce-based CSP.
 */
final class CaptchaFoxProvider implements CaptchaProviderInterface
{
    /**
     * Provider name. Must equal the plugin element, otherwise Joomla does not find the provider.
     */
    public const NAME = 'captchafox';

    /**
     * Log category; the plugin registers a logger for it (administrator/logs/plg_captcha_captchafox.php).
     */
    public const LOG_CATEGORY = 'plg_captcha_captchafox';

    /**
     * POST field into which the CaptchaFox widget writes its token.
     */
    private const RESPONSE_FIELD = 'cf-captcha-response';

    /**
     * CaptchaFox widget script. It must be loaded from the CDN: it locates its own script tag by URL.
     * Explicit rendering lets the init script control each widget; the "?" also keeps Joomla from
     * appending its media version to the URL.
     */
    private const API_URL = 'https://cdn.captchafox.com/api.js?render=explicit&onload=captchaFoxJoomlaOnLoad';

    private const ASSET_INIT = 'plg_captcha_captchafox.init';

    private const ASSET_API = 'plg_captcha_captchafox.api';

    public function __construct(
        private Registry $params,
        private ?CMSApplicationInterface $application,
        private SiteVerifyClient $client
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
     *
     * @throws  \RuntimeException  If the plugin is not configured.
     */
    public function display(string $name = '', array $attributes = []): string
    {
        $siteKey = $this->getSiteKey();

        if ($siteKey === '') {
            throw new \RuntimeException(Text::_('PLG_CAPTCHA_CAPTCHAFOX_ERROR_NOT_CONFIGURED'));
        }

        if ($this->application instanceof CMSWebApplicationInterface) {
            $this->useAssets($this->application->getDocument()->getWebAssetManager());
        }

        $html = [
            'class'                  => 'captchafox-joomla',
            'data-captchafox-joomla' => '',
            'data-sitekey'           => $siteKey,
        ];

        $id = (string) ($attributes['id'] ?? '');

        if ($id !== '') {
            $html['id'] = $id;
        }

        return '<div' . $this->renderAttributes($html) . '></div>';
    }

    /**
     * Joomla passes the value of the captcha field itself, which the widget does not fill: the token
     * arrives in its own POST field. $code is still used when a form passes it explicitly.
     *
     * @throws  \RuntimeException  If the plugin is not configured, or the API is unreachable and the
     *                              plugin is set to block. Joomla shows the message in the form.
     */
    public function checkAnswer(?string $code = null): bool
    {
        $token = trim((string) $code);

        if ($token === '' && $this->application !== null) {
            $token = trim($this->application->getInput()->post->getString(self::RESPONSE_FIELD, ''));
        }

        // An unsolved widget sends no token; there is nothing to ask CaptchaFox about.
        if ($token === '') {
            return false;
        }

        $siteKey = $this->getSiteKey();
        $secret  = trim((string) $this->params->get('secret', ''));

        if ($siteKey === '' || $secret === '') {
            throw new \RuntimeException(Text::_('PLG_CAPTCHA_CAPTCHAFOX_ERROR_NOT_CONFIGURED'));
        }

        $result = $this->client->verify($secret, $token, $siteKey);

        if ($result->status === VerificationResult::VALID) {
            return true;
        }

        if ($result->status === VerificationResult::INVALID) {
            Log::add('Answer rejected by CaptchaFox: ' . implode(', ', $result->errorCodes), Log::DEBUG, self::LOG_CATEGORY);

            return false;
        }

        $allow = $this->params->get('api_unavailable', 'block') === 'allow';

        Log::add(
            sprintf('CaptchaFox API unavailable (%s), form %s.', $result->reason, $allow ? 'let through' : 'blocked'),
            Log::WARNING,
            self::LOG_CATEGORY
        );

        if ($allow) {
            return true;
        }

        throw new \RuntimeException(Text::_('PLG_CAPTCHA_CAPTCHAFOX_ERROR_UNAVAILABLE'));
    }

    public function setupField(FormField $field, \SimpleXMLElement $element): void
    {
    }

    private function getSiteKey(): string
    {
        return trim((string) $this->params->get('sitekey', ''));
    }

    /**
     * Registers the init script and the CaptchaFox API once per page. The API depends on the init
     * script, so the onload callback exists before the API calls it.
     */
    private function useAssets(WebAssetManager $assets): void
    {
        if (!$assets->assetExists('script', self::ASSET_INIT)) {
            $assets->registerScript(self::ASSET_INIT, 'plg_captcha_captchafox/captchafox.js', [], ['defer' => true]);
        }

        if (!$assets->assetExists('script', self::ASSET_API)) {
            $assets->registerScript(self::ASSET_API, self::API_URL, [], ['defer' => true], [self::ASSET_INIT]);
        }

        $assets->useScript(self::ASSET_API);
    }

    /**
     * @param   array<string, string>  $attributes  Attribute names and values.
     */
    private function renderAttributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $key => $value) {
            $html .= ' ' . $key . ($value === '' ? '' : '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"');
        }

        return $html;
    }
}
