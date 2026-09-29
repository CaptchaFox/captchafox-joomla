<?php

/**
 * @package     CaptchaFox.Plugin
 * @subpackage  Captcha.CaptchaFox
 *
 * @copyright   (C) 2026 Scoria Labs GmbH
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace CaptchaFox\Plugin\Captcha\CaptchaFox\Verification;

use Joomla\Http\HttpFactory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Sends a token to the CaptchaFox siteverify endpoint.
 *
 * Uses the HTTP client of the Joomla framework, which exists unchanged in Joomla 5.4 and 6. There is
 * no retry: real tokens are single-use, so a second attempt could only fail.
 */
final class SiteVerifyClient
{
    public const ENDPOINT = 'https://api.captchafox.com/siteverify';

    public const TIMEOUT_SECONDS = 5;

    /**
     * @param   HttpFactory           $httpFactory  Creates the HTTP client.
     * @param   array<string, mixed>  $httpOptions  Client options, e.g. user agent and curl proxy settings.
     */
    public function __construct(
        private HttpFactory $httpFactory,
        private array $httpOptions = []
    ) {
    }

    public function verify(string $secret, string $token, string $siteKey): VerificationResult
    {
        try {
            $response = $this->httpFactory->getHttp($this->httpOptions)->post(
                self::ENDPOINT,
                ['secret' => $secret, 'response' => $token, 'sitekey' => $siteKey],
                [],
                self::TIMEOUT_SECONDS
            );
        } catch (\Throwable $exception) {
            return VerificationResult::unavailable('request failed: ' . $exception->getMessage());
        }

        return VerificationResult::fromResponse($response->getStatusCode(), (string) $response->getBody());
    }
}
