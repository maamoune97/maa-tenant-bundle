<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Resolver\Http;

use Maa\TenantBundle\Resolver\TenantResolverInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves the tenant from a configurable HTTP header (e.g. X-Tenant-Code).
 *
 * Not enabled by default — tag this service with `maa_tenant.http_resolver`
 * and set a priority in your application's service configuration to enable it.
 */
final class HeaderTenantResolver implements TenantResolverInterface
{
    public function __construct(private readonly string $headerName = 'X-Tenant-Code') {}

    public function resolve(Request $request): ?string
    {
        $value = $request->headers->get($this->headerName);

        return ($value !== null && $value !== '') ? $value : null;
    }
}
