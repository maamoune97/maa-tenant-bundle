<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Context;

use Maa\TenantBundle\Entity\Tenant;

interface TenantContextInterface
{
    public function setTenant(Tenant $tenant): void;

    public function getTenant(): ?Tenant;

    public function hasTenant(): bool;
}
