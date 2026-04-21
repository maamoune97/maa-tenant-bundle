<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Tests\Unit\Context;

use Maa\TenantBundle\Context\TenantContext;
use Maa\TenantBundle\Entity\Tenant;
use PHPUnit\Framework\TestCase;

final class TenantContextTest extends TestCase
{
    public function testHasNoTenantByDefault(): void
    {
        $ctx = new TenantContext();

        self::assertFalse($ctx->hasTenant());
        self::assertNull($ctx->getTenant());
    }

    public function testSetAndGetTenant(): void
    {
        $ctx = new TenantContext();
        $tenant = new Tenant('acme', 'Acme Corp');

        $ctx->setTenant($tenant);

        self::assertTrue($ctx->hasTenant());
        self::assertSame($tenant, $ctx->getTenant());
    }

    public function testSetTenantOverwritesPrevious(): void
    {
        $ctx = new TenantContext();
        $ctx->setTenant(new Tenant('foo', 'Foo'));
        $bar = new Tenant('bar', 'Bar');
        $ctx->setTenant($bar);

        self::assertSame($bar, $ctx->getTenant());
    }
}
