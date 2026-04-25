<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Event;

use Maa\TenantBundle\Entity\Tenant;

final class TenantDeletedEvent
{
    public function __construct(
        private readonly Tenant $tenant,
        private readonly string $databaseName,
    ) {}

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    /**
     * The database name associated with the deleted tenant (still exists — not dropped).
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }
}
