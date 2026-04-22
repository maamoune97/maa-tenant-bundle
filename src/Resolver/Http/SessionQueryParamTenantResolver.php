<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Resolver\Http;

use Maa\TenantBundle\Resolver\TenantResolverInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Development resolver: reads ?_tenant=<code> from the URL and stores it in the session.
 * Subsequent requests without the param reuse the session value, so navigation works normally.
 * Passing ?_tenant=<other> switches the tenant for the rest of the session.
 */
final class SessionQueryParamTenantResolver implements TenantResolverInterface
{
    private const SESSION_KEY = '_maa_tenant_code';

    public function __construct(private readonly string $paramName = '_tenant') {}

    public function resolve(Request $request): ?string
    {
        $code = $request->query->get($this->paramName);

        if ($code !== null && $code !== '') {
            $request->getSession()->set(self::SESSION_KEY, (string) $code);
            return (string) $code;
        }

        $fromSession = $request->getSession()->get(self::SESSION_KEY);

        return ($fromSession !== null && $fromSession !== '') ? (string) $fromSession : null;
    }
}
