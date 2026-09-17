<?php

declare(strict_types=1);

namespace Integrify\EPoint\Tests;

use Integrify\EPoint\EPointConfig;
use Integrify\Exception\MissingConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * `EPointConfig` və imza hesablaması.
 */
final class EPointConfigTest extends TestCase
{
    /** @var list<string> */
    private const VARIABLES = [
        'EPOINT_PUBLIC_KEY',
        'EPOINT_PRIVATE_KEY',
        'EPOINT_INTERFACE_LANG',
        'EPOINT_SUCCESS_REDIRECT_URL',
        'EPOINT_FAILED_REDIRECT_URL',
    ];

    protected function setUp(): void
    {
        // Test dəsti heç bir environment dəyişənindən asılı olmamalıdır, və testlər
        // bir-birinin ardıcıllığından da asılı olmamalıdır.
        foreach (self::VARIABLES as $variable) {
            putenv($variable);
            unset($_ENV[$variable], $_SERVER[$variable]);
        }
    }

    protected function tearDown(): void
    {
        $this->setUp();
    }

    public function testTheSignatureFollowsEPointsFormula(): void
    {
        $config = new EPointConfig('i000000001', 'private');

        $this->assertSame(
            base64_encode(sha1('private' . 'payload' . 'private', true)),
            $config->signature('payload'),
        );
    }

    public function testTheSignatureBase64sTheRawDigestNotTheHexString(): void
    {
        $config = new EPointConfig('i000000001', 'private');

        // `base64_encode(sha1($s))` 40 simvollu hex sətri base64-ləyir və EPoint onu
        // `Signature did not match` ilə rədd edir. Xam digest 20 bayt → 28 simvol.
        $this->assertSame(28, strlen($config->signature('payload')));
        $this->assertNotSame(base64_encode(sha1('private' . 'payload' . 'private')), $config->signature('payload'));
    }

    public function testVerifyAcceptsItsOwnSignature(): void
    {
        $config = new EPointConfig('i000000001', 'private');

        $this->assertTrue($config->verify('payload', $config->signature('payload')));
        $this->assertFalse($config->verify('payload', 'wrong'));
        $this->assertFalse($config->verify('other-payload', $config->signature('payload')));
    }

    public function testAnotherPrivateKeyProducesAnotherSignature(): void
    {
        $mine = new EPointConfig('i000000001', 'private');
        $theirs = new EPointConfig('i000000001', 'other');

        $this->assertNotSame($mine->signature('payload'), $theirs->signature('payload'));
        $this->assertFalse($mine->verify('payload', $theirs->signature('payload')));
    }

    public function testTheLanguageDefaultsToAzerbaijani(): void
    {
        $config = new EPointConfig('i000000001', 'private');

        $this->assertSame('az', $config->language);
        $this->assertSame('az', EPointConfig::DEFAULT_LANGUAGE);
    }

    public function testFromEnvironmentReadsTheKeys(): void
    {
        putenv('EPOINT_PUBLIC_KEY=i000000001');
        putenv('EPOINT_PRIVATE_KEY=secret');

        $config = EPointConfig::fromEnvironment();

        $this->assertSame('i000000001', $config->publicKey);
        $this->assertSame('secret', $config->privateKey);
        $this->assertSame('az', $config->language);
        $this->assertNull($config->successRedirectUrl);
        $this->assertNull($config->errorRedirectUrl);
    }

    public function testFromEnvironmentMapsFailedToErrorRedirectUrl(): void
    {
        putenv('EPOINT_PUBLIC_KEY=i000000001');
        putenv('EPOINT_PRIVATE_KEY=secret');
        putenv('EPOINT_SUCCESS_REDIRECT_URL=https://shop.az/ok');
        putenv('EPOINT_FAILED_REDIRECT_URL=https://shop.az/fail');

        $config = EPointConfig::fromEnvironment();

        // Dəyişən `FAILED`, API field-i isə `error_redirect_url` adlanır. Adlar
        // Python kitabxanası ilə eyni saxlanılıb ki, bir `.env` paylaşıla bilsin.
        $this->assertSame('https://shop.az/ok', $config->successRedirectUrl);
        $this->assertSame('https://shop.az/fail', $config->errorRedirectUrl);
    }

    public function testFromEnvironmentOverridesTheLanguage(): void
    {
        putenv('EPOINT_PUBLIC_KEY=i000000001');
        putenv('EPOINT_PRIVATE_KEY=secret');
        putenv('EPOINT_INTERFACE_LANG=en');

        $this->assertSame('en', EPointConfig::fromEnvironment()->language);
    }

    public function testAnEmptyLanguageFallsBackToTheDefault(): void
    {
        putenv('EPOINT_PUBLIC_KEY=i000000001');
        putenv('EPOINT_PRIVATE_KEY=secret');
        putenv('EPOINT_INTERFACE_LANG=');

        $this->assertSame('az', EPointConfig::fromEnvironment()->language);
    }

    public function testAMissingPublicKeyIsReported(): void
    {
        putenv('EPOINT_PRIVATE_KEY=secret');

        $this->expectException(MissingConfiguration::class);

        EPointConfig::fromEnvironment();
    }

    public function testAMissingPrivateKeyIsReported(): void
    {
        putenv('EPOINT_PUBLIC_KEY=i000000001');

        $this->expectException(MissingConfiguration::class);

        EPointConfig::fromEnvironment();
    }

    public function testTheServerArrayIsAFallbackForGetenv(): void
    {
        // php-fpm bəzi quraşdırmalarda dəyişənləri yalnız `$_SERVER`-ə qoyur.
        $_SERVER['EPOINT_PUBLIC_KEY'] = 'i000000001';
        $_SERVER['EPOINT_PRIVATE_KEY'] = 'secret';

        $config = EPointConfig::fromEnvironment();

        $this->assertSame('i000000001', $config->publicKey);
        $this->assertSame('secret', $config->privateKey);
    }
}
