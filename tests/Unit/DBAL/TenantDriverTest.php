<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Tests\Unit\DBAL;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Maa\TenantBundle\Context\TenantContext;
use Maa\TenantBundle\DBAL\TenantDriver;
use Maa\TenantBundle\Entity\Tenant;
use PHPUnit\Framework\TestCase;

final class TenantDriverTest extends TestCase
{
    public function testConnectInjectsTenantDatabaseName(): void
    {
        $context = new TenantContext();
        $context->setTenant(new Tenant('acme', 'Acme Corp'));

        $capturedParams = null;
        $innerDriver = $this->createMock(Driver::class);
        $innerDriver->expects(self::once())
            ->method('connect')
            ->with(self::callback(function (array $params) use (&$capturedParams): bool {
                $capturedParams = $params;
                return true;
            }))
            ->willReturn($this->createMock(DriverConnection::class));

        $driver = new TenantDriver($innerDriver, $context, 'tenant_');
        $driver->connect(['dbname' => 'placeholder', 'host' => 'localhost']);

        self::assertSame('tenant_acme', $capturedParams['dbname']);
    }

    public function testConnectLeavesParamsUnchangedWithoutTenant(): void
    {
        $context = new TenantContext();

        $capturedParams = null;
        $innerDriver = $this->createMock(Driver::class);
        $innerDriver->expects(self::once())
            ->method('connect')
            ->with(self::callback(function (array $params) use (&$capturedParams): bool {
                $capturedParams = $params;
                return true;
            }))
            ->willReturn($this->createMock(DriverConnection::class));

        $driver = new TenantDriver($innerDriver, $context, 'tenant_');
        $driver->connect(['dbname' => 'original', 'host' => 'localhost']);

        self::assertSame('original', $capturedParams['dbname']);
    }
}
