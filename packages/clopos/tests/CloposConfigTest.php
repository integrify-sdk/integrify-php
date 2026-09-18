<?php

declare(strict_types=1);

namespace Integrify\Clopos\Tests;

use Integrify\Clopos\CloposConfig;
use Integrify\Exception\MissingConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * Konfiqurasiya.
 */
final class CloposConfigTest extends TestCase
{
    public function testTheDefaultBaseUrlIsTheOpenApiV2Root(): void
    {
        $this->assertSame(
            'https://integrations.clopos.com/open-api/v2/',
            (new CloposConfig())->baseUrl,
        );
    }

    public function testAnEmptyEnvironmentGivesAnEmptyConfiguration(): void
    {
        // Test suite-i heç bir mühit dəyişəni olmadan keçməlidir.
        $config = CloposConfig::fromEnvironment();

        $this->assertNull($config->clientId);
        $this->assertNull($config->clientSecret);
        $this->assertNull($config->brand);
        $this->assertNull($config->integratorId);
        $this->assertNull($config->venueId);
    }

    public function testCredentialsAreReturnedWithTheServicesFieldNames(): void
    {
        $this->assertSame([
            'client_id' => 'cid',
            'client_secret' => 'secret',
            'brand' => 'brand',
            'integrator_id' => '7',
        ], Factory::config()->credentials());
    }

    public function testAMissingCredentialNamesTheVariable(): void
    {
        $this->expectException(MissingConfiguration::class);
        $this->expectExceptionMessageMatches('/CLOPOS_BRAND/');

        Factory::config(brand: null)->credentials();
    }

    public function testAnEmptyCredentialCountsAsMissing(): void
    {
        $this->expectException(MissingConfiguration::class);
        $this->expectExceptionMessageMatches('/CLOPOS_CLIENT_SECRET/');

        Factory::config(clientSecret: '')->credentials();
    }

    public function testTheVenueIdIsOptional(): void
    {
        $this->assertNull(Factory::config()->venueId);
        $this->assertSame('3', Factory::config(venueId: '3')->venueId);

        // Filial açarlardan asılı deyil: token olmadan da göndərilə bilər.
        $this->assertSame('3', Factory::config(clientId: null, venueId: '3')->venueId);
    }
}
