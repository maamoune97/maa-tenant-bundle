<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Context;

use Maa\TenantBundle\Entity\Tenant;

final class TenantContext implements TenantContextInterface
{
    private ?Tenant $tenant = null;

    public function setTenant(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }
}
