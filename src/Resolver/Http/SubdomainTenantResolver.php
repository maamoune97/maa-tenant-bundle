<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Resolver\Http;

use Maa\TenantBundle\Resolver\TenantResolverInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves the tenant from the first subdomain segment (e.g. "acme" in "acme.example.com").
 *
 * Hosts without a subdomain (bare domain or "www") are skipped.
 */
final class SubdomainTenantResolver implements TenantResolverInterface
{
    /**
     * @param string[] $ignoredSubdomains Subdomains that should not be treated as tenant codes (e.g. "www", "api").
     */
    public function __construct(private readonly array $ignoredSubdomains = ['www', 'api']) {}

    public function resolve(Request $request): ?string
    {
        $host = $request->getHost();
        $parts = explode('.', $host);

        // Need at least <subdomain>.<domain>.<tld> to have a real subdomain.
        if (\count($parts) < 3) {
            return null;
        }

        $subdomain = $parts[0];

        if (in_array($subdomain, $this->ignoredSubdomains, true)) {
            return null;
        }

        return $subdomain ?: null;
    }
}
