<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Resolver;

use Symfony\Component\HttpFoundation\Request;

/**
 * Delegates resolution to a prioritised list of resolvers, returning the first non-null result.
 */
final class ChainTenantResolver implements TenantResolverInterface
{
    /** @param TenantResolverInterface[] $resolvers Injected by TenantResolverPass, ordered by priority. */
    public function __construct(private readonly iterable $resolvers = []) {}

    public function resolve(Request $request): ?string
    {
        foreach ($this->resolvers as $resolver) {
            $code = $resolver->resolve($request);
            if ($code !== null) {
                return $code;
            }
        }

        return null;
    }
}