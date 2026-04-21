<?php

declare(strict_types=1);

namespace Maa\TenantBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class MaaTenantExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        $container->setParameter('maa_tenant.registry_url', $config['registry_url']);
        $container->setParameter('maa_tenant.tenant_connection', $config['tenant_connection']);
        $container->setParameter('maa_tenant.tenant_db_prefix', $config['tenant_db_prefix']);
        $container->setParameter('maa_tenant.http.required', $config['http']['required']);
        $container->setParameter('maa_tenant.cli.tenant_commands', $config['cli']['tenant_commands']);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $srcDir = \dirname(__DIR__);

        // 1. Registry connection + dedicated EM for the Tenant entity.
        //    Mapping this bundle explicitly to "maa_tenant" also prevents DoctrineBundle's
        //    auto_mapping from pulling Tenant into the app's default EM.
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'connections' => [
                    'maa_tenant_registry' => [
                        'url' => $config['registry_url'],
                        'driver' => 'pdo_pgsql',
                    ],
                ],
            ],
            'orm' => [
                'entity_managers' => [
                    'maa_tenant' => [
                        'connection' => 'maa_tenant_registry',
                        'mappings' => [
                            'MaaTenant' => [
                                'is_bundle' => false,
                                'type' => 'attribute',
                                'dir' => $srcDir . '/Entity',
                                'prefix' => 'Maa\TenantBundle\Entity',
                                'alias' => 'MaaTenant',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // 2. Attach the per-tenant DBAL middleware to the app's tenant connection.
        //    Prepended separately so it merges cleanly even when the app uses the
        //    single-connection shorthand (url:) rather than the connections: map.
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'connections' => [
                    $config['tenant_connection'] => [
                        'middlewares' => ['maa_tenant.dbal.middleware'],
                    ],
                ],
            ],
        ]);
    }

    public function getAlias(): string
    {
        return 'maa_tenant';
    }
}
