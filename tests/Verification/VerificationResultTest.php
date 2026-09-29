<?php

/**
 * @package     CaptchaFox.Plugin
 * @subpackage  Captcha.CaptchaFox
 *
 * @copyright   (C) 2026 Scoria Labs GmbH
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

declare(strict_types=1);

namespace CaptchaFox\Plugin\Captcha\CaptchaFox\Tests\Verification;

use CaptchaFox\Plugin\Captcha\CaptchaFox\Verification\VerificationResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Classification of siteverify responses: valid, rejected by CaptchaFox, or not checkable.
 */
final class VerificationResultTest extends TestCase
{
    public function testSuccessTrueIsValid(): void
    {
        $result = VerificationResult::fromResponse(200, '{"success":true}');

        self::assertSame(VerificationResult::VALID, $result->status);
    }

    public function testSuccessFalseIsInvalidWithErrorCodes(): void
    {
        $result = VerificationResult::fromResponse(200, '{"success":false,"error-codes":["timeout-or-duplicate"]}');

        self::assertSame(VerificationResult::INVALID, $result->status);
        self::assertSame(['timeout-or-duplicate'], $result->errorCodes);
    }

    public function testSuccessFalseWithoutErrorCodesIsInvalid(): void
    {
        $result = VerificationResult::fromResponse(200, '{"success":false}');

        self::assertSame(VerificationResult::INVALID, $result->status);
        self::assertSame([], $result->errorCodes);
    }

    public function testNonStringErrorCodesAreDropped(): void
    {
        $result = VerificationResult::fromResponse(200, '{"success":false,"error-codes":["bad-request",42,null]}');

        self::assertSame(['bad-request'], $result->errorCodes);
    }

    /**
     * Only a boolean true counts. A truthy value such as "true" or 1 is not a success.
     */
    #[DataProvider('truthyButNotTrue')]
    public function testOnlyBooleanTrueIsValid(string $body): void
    {
        self::assertSame(VerificationResult::INVALID, VerificationResult::fromResponse(200, $body)->status);
    }

    /**
     * @return  array<string, array{string}>
     */
    public static function truthyButNotTrue(): array
    {
        return [
            'string "true"' => ['{"success":"true"}'],
            'number 1'      => ['{"success":1}'],
        ];
    }

    /**
     * Cases in which the answer could not be checked; the admin setting decides what happens then.
     */
    #[DataProvider('unavailableResponses')]
    public function testUnavailable(int $status, string $body, string $reasonFragment): void
    {
        $result = VerificationResult::fromResponse($status, $body);

        self::assertSame(VerificationResult::UNAVAILABLE, $result->status);
        self::assertStringContainsString($reasonFragment, $result->reason);
    }

    /**
     * @return  array<string, array{int, string, string}>
     */
    public static function unavailableResponses(): array
    {
        return [
            'redirect'         => [301, '', 'HTTP status 301'],
            'server error'     => [500, '{"success":true}', 'HTTP status 500'],
            'rate limited'     => [429, '', 'HTTP status 429'],
            'no status'        => [0, '', 'HTTP status 0'],
            'not JSON'         => [200, '<html>maintenance</html>', 'not valid JSON'],
            'empty body'       => [200, '', 'not valid JSON'],
            'JSON array'       => [200, '[true]', 'no "success" field'],
            'JSON scalar'      => [200, 'true', 'no "success" field'],
            'no success field' => [200, '{"error-codes":[]}', 'no "success" field'],
        ];
    }

    public function testUnavailableKeepsTheReason(): void
    {
        $result = VerificationResult::unavailable('request failed: timeout');

        self::assertSame(VerificationResult::UNAVAILABLE, $result->status);
        self::assertSame('request failed: timeout', $result->reason);
    }
}
