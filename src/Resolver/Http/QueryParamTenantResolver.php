<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Resolver\Http;

use Maa\TenantBundle\Resolver\TenantResolverInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves the tenant from a query parameter (e.g. ?_tenant=acme).
 *
 * Intended for local development in fullstack Symfony apps where subdomains
 * are not available. Not enabled by default — opt in via services_dev.yaml.
 */
final class QueryParamTenantResolver implements TenantResolverInterface
{
    public function __construct(private readonly string $paramName = '_tenant') {}

    public function resolve(Request $request): ?string
    {
        $value = $request->query->get($this->paramName);

        return ($value !== null && $value !== '') ? (string) $value : null;
    }
}
