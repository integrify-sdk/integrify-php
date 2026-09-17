<?php

declare(strict_types=1);

namespace Integrify\Lsim\Tests;

use Integrify\Exception\MissingConfiguration;
use Integrify\Lsim\LsimConfig;
use PHPUnit\Framework\TestCase;

/**
 * `LsimConfig` və imza hesablaması.
 */
final class LsimConfigTest extends TestCase
{
    /** @var list<string> */
    private const VARIABLES = ['LSIM_LOGIN', 'LSIM_PASSWORD', 'LSIM_SENDER_NAME'];

    protected function setUp(): void
    {
        // Test dəsti heç bir environment dəyişənindən asılı olmamalıdır, və testlər
        // bir-birinin ardıcıllığından da asılı olmamalıdır.
        foreach (self::VARIABLES as $variable) {
            putenv($variable);
            unset($_ENV[$variable], $_SERVER[$variable]);
        }
    }

    public function testTheSignatureFollowsLsimsFormula(): void
    {
        $config = new LsimConfig('login', 'password', 'Sender');

        $this->assertSame(
            md5(md5('password') . 'login' . 'text' . '994501234567' . 'Sender'),
            $config->signature('text', '994501234567'),
        );
    }

    public function testASenderOverrideChangesTheSignature(): void
    {
        $config = new LsimConfig('login', 'password', 'Sender');

        $this->assertSame(
            md5(md5('password') . 'login' . 'text' . '994501234567' . 'Other'),
            $config->signature('text', '994501234567', 'Other'),
        );
    }

    public function testTheBalanceSignatureOmitsTheMessage(): void
    {
        $config = new LsimConfig('login', 'password');

        $this->assertSame(md5(md5('password') . 'login'), $config->balanceSignature());
    }

    public function testFromEnvironmentReadsAllThreeVariables(): void
    {
        putenv('LSIM_LOGIN=env-login');
        putenv('LSIM_PASSWORD=env-password');
        putenv('LSIM_SENDER_NAME=EnvSender');

        $config = LsimConfig::fromEnvironment();

        $this->assertSame('env-login', $config->login);
        $this->assertSame('env-password', $config->password);
        $this->assertSame('EnvSender', $config->senderName);
    }

    public function testTheSenderNameIsOptional(): void
    {
        putenv('LSIM_LOGIN=env-login');
        putenv('LSIM_PASSWORD=env-password');

        $this->assertSame('', LsimConfig::fromEnvironment()->senderName);
    }

    public function testAMissingLoginIsReportedClearly(): void
    {
        putenv('LSIM_PASSWORD=env-password');

        try {
            LsimConfig::fromEnvironment();
            $this->fail('Expected MissingConfiguration.');
        } catch (MissingConfiguration $failure) {
            $this->assertSame('LSIM_LOGIN', $failure->variable);
            $this->assertStringContainsString('LSIM_LOGIN', $failure->getMessage());
        }
    }

    public function testAMissingPasswordIsReportedClearly(): void
    {
        putenv('LSIM_LOGIN=env-login');

        $this->expectException(MissingConfiguration::class);

        LsimConfig::fromEnvironment();
    }

    public function testAnEmptyVariableCountsAsAbsent(): void
    {
        putenv('LSIM_LOGIN=');
        putenv('LSIM_PASSWORD=env-password');

        $this->expectException(MissingConfiguration::class);

        LsimConfig::fromEnvironment();
    }
}
