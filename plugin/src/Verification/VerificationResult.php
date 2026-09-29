<?php

/**
 * @package     CaptchaFox.Plugin
 * @subpackage  Captcha.CaptchaFox
 *
 * @copyright   (C) 2026 Scoria Labs GmbH
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace CaptchaFox\Plugin\Captcha\CaptchaFox\Verification;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Outcome of a siteverify request.
 *
 * Three outcomes, because an answer that CaptchaFox rejected is handled differently from an answer
 * that could not be checked at all: the first is always rejected, the second follows the admin
 * setting for an unreachable API.
 */
final class VerificationResult
{
    public const VALID = 'valid';

    public const INVALID = 'invalid';

    public const UNAVAILABLE = 'unavailable';

    /**
     * @param   string    $status      One of the class constants.
     * @param   string[]  $errorCodes  Error codes reported by CaptchaFox (INVALID only).
     * @param   string    $reason      Why the answer could not be checked (UNAVAILABLE only).
     */
    private function __construct(
        public readonly string $status,
        public readonly array $errorCodes = [],
        public readonly string $reason = ''
    ) {
    }

    /**
     * Classifies a siteverify response. A 2xx status alone means nothing: only "success": true is
     * a valid answer.
     */
    public static function fromResponse(int $statusCode, string $body): self
    {
        if ($statusCode < 200 || $statusCode >= 300) {
            return self::unavailable('HTTP status ' . $statusCode);
        }

        try {
            $data = json_decode($body, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return self::unavailable('response is not valid JSON');
        }

        if (!\is_array($data) || !\array_key_exists('success', $data)) {
            return self::unavailable('response has no "success" field');
        }

        if ($data['success'] === true) {
            return new self(self::VALID);
        }

        $codes = \is_array($data['error-codes'] ?? null) ? $data['error-codes'] : [];

        return new self(self::INVALID, array_values(array_filter($codes, 'is_string')));
    }

    public static function unavailable(string $reason): self
    {
        return new self(self::UNAVAILABLE, [], $reason);
    }
}
