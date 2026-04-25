<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Event;

use Maa\TenantBundle\Entity\Tenant;

final class TenantCreatedEvent
{
    public function __construct(
        private readonly Tenant $tenant,
        private readonly string $databaseName,
        private readonly bool $databaseCreated,
    ) {}

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    /**
     * The expected/configured database name, whether or not it was provisioned.
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * False when --skip-db was passed; true when the database was actually created.
     */
    public function isDatabaseCreated(): bool
    {
        return $this->databaseCreated;
    }
}
