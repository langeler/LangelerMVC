<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Console\Commands\ReleaseCheckCommand;
use App\Providers\CoreProvider;
use PHPUnit\Framework\TestCase;

final class ReleaseReadinessTest extends TestCase
{
    public function testReleaseCheckPassesLocalReleaseGateAndReportsExternalWork(): void
    {
        $command = $this->command();
        $payload = $command->inspect();

        self::assertSame(200, $payload['status']);
        self::assertTrue((bool) $payload['healthy']);
        self::assertSame([], $payload['errors']);
        self::assertTrue((bool) $payload['checks']['release_docs']['ok']);
        self::assertTrue((bool) $payload['checks']['environment_template']['ok']);
        self::assertTrue((bool) $payload['checks']['data_sql_reference']['ok']);
        self::assertContains('Data/Orders.sql', $payload['checks']['data_sql_reference']['required_files']);
        self::assertSame([], $payload['checks']['data_sql_reference']['stale_tables']);
        self::assertTrue((bool) $payload['checks']['framework_routes']['ok']);
        self::assertTrue((bool) $payload['checks']['module_surface']['ok']);
        self::assertArrayHasKey('OrderModule', $payload['checks']['module_surface']['modules']);
        self::assertTrue((bool) $payload['checks']['payment_surface']['ok']);
        self::assertContains('paypal', $payload['checks']['payment_surface']['catalog']);
        self::assertContains('swish', $payload['checks']['payment_surface']['catalog']);
        self::assertArrayHasKey('live_ready', $payload['checks']['payment_surface']['drivers']['paypal']);
        self::assertTrue((bool) $payload['checks']['commerce_surface']['ok']);
        self::assertContains('postnord', $payload['checks']['commerce_surface']['carrier_adapters']);
        self::assertContains('ups', $payload['checks']['commerce_surface']['carrier_adapters']);
        self::assertTrue((bool) $payload['checks']['template_accessibility']['ok']);
        self::assertSame([], $payload['checks']['template_accessibility']['raw_php_templates']);
        self::assertSame([], $payload['checks']['template_accessibility']['images_without_alt']);
        self::assertSame([], $payload['checks']['template_accessibility']['unlabelled_controls']);
        self::assertContains('mysql', $payload['external_required']['database_cache_session_matrix']);
        self::assertContains('postnord', $payload['external_required']['live_carriers']);
        self::assertContains('browser_accessibility_pass', array_keys($payload['external_required']));
    }

    public function testReleaseCheckStrictModeFailsUntilLiveEnvironmentWarningsAreResolved(): void
    {
        $command = $this->command();
        $payload = $command->inspect(true);

        self::assertSame(503, $payload['status']);
        self::assertFalse((bool) $payload['healthy']);
        self::assertNotSame([], $payload['warnings']);
        self::assertContains('Payment driver [testing] is still in reference/testing mode.', $payload['warnings']);
    }

    private function command(): ReleaseCheckCommand
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $command = $provider->resolveClass(ReleaseCheckCommand::class);

        self::assertInstanceOf(ReleaseCheckCommand::class, $command);

        return $command;
    }
}
