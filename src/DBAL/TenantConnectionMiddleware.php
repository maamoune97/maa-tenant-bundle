<?php

declare(strict_types=1);

namespace Maa\TenantBundle\DBAL;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Maa\TenantBundle\Context\TenantContextInterface;

/**
 * DBAL middleware that routes every connection attempt to the current tenant's database.
 *
 * Registered via DoctrineBundle's middleware configuration on the tenant connection.
 * When no tenant is active (e.g. during warmup or CLI commands not needing a tenant)
 * the underlying connection params are left unchanged.
 */
final class TenantConnectionMiddleware implements Middleware
{
    public function __construct(
        private readonly TenantContextInterface $context,
        private readonly string $dbPrefix,
    ) {}

    public function wrap(Driver $driver): Driver
    {
        return new TenantDriver($driver, $this->context, $this->dbPrefix);
    }
}
