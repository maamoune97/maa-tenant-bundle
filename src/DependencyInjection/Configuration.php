<?php

declare(strict_types=1);

namespace Maa\TenantBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('maa_tenant');
        $root = $tree->getRootNode();

        $root
            ->children()
                ->scalarNode('registry_url')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('DSN for the central tenant registry PostgreSQL database.')
                ->end()
                ->scalarNode('tenant_connection')
                    ->defaultValue('default')
                    ->info('Name of the Doctrine DBAL connection to intercept for per-tenant routing.')
                ->end()
                ->scalarNode('tenant_db_prefix')
                    ->defaultValue('tenant_')
                    ->info('Prefix prepended to the tenant code to form the database name.')
                ->end()
                ->arrayNode('http')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('required')
                            ->defaultTrue()
                            ->info('Throw TenantNotResolvedException when no tenant can be resolved from the request.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cli')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('tenant_commands')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                            ->info('Console command names that require the --tenant option.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $tree;
    }
}
