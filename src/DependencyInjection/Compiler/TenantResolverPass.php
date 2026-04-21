<?php

declare(strict_types=1);

namespace Maa\TenantBundle\DependencyInjection\Compiler;

use Maa\TenantBundle\Resolver\ChainTenantResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects all tagged tenant resolvers and injects them into the chain resolver.
 */
final class TenantResolverPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ChainTenantResolver::class)) {
            return;
        }

        $resolvers = $this->findAndSortTaggedServices('maa_tenant.http_resolver', $container);

        $container->getDefinition(ChainTenantResolver::class)
            ->setArgument('$resolvers', $resolvers);
    }
}
