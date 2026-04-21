<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Resolver;

use Symfony\Component\HttpFoundation\Request;

/**
 * Extracts a tenant code from an HTTP request.
 *
 * Tag your implementation with `maa_tenant.http_resolver` and set a `priority`
 * attribute (higher = tried first) so the ChainTenantResolver picks it up.
 */
interface TenantResolverInterface
{
    /**
     * Returns the tenant code or null if this resolver cannot determine it.
     */
    public function resolve(Request $request): ?string;
}
