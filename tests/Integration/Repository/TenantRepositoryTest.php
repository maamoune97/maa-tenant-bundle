<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Tests\Integration\Repository;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Maa\TenantBundle\Entity\Tenant;
use Maa\TenantBundle\Repository\TenantRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * Requires a live PostgreSQL instance (provided by docker-compose).
 * Skip gracefully when TENANT_REGISTRY_DATABASE_URL is absent.
 */
final class TenantRepositoryTest extends TestCase
{
    private EntityManager $em;
    private TenantRepository $repository;

    protected function setUp(): void
    {
        $url = getenv('TENANT_REGISTRY_DATABASE_URL') ?: ($_ENV['TENANT_REGISTRY_DATABASE_URL'] ?? null);

        if (!$url) {
            self::markTestSkipped('TENANT_REGISTRY_DATABASE_URL is not set.');
        }

        if (!Type::hasType(UuidType::NAME)) {
            Type::addType(UuidType::NAME, UuidType::class);
        }

        $ormConfig = ORMSetup::createAttributeMetadataConfiguration(
            paths: [\dirname(__DIR__, 3) . '/src/Entity'],
            isDevMode: true,
        );
        // symfony/var-exporter 8.x dropped LazyGhostTrait; use PHP 8.4+ native lazy objects instead.
        $ormConfig->enableNativeLazyObjects(true);

        // DBAL 4 removed url support from DriverManager — parse via DsnParser first.
        $params = (new DsnParser(['postgresql' => 'pdo_pgsql', 'postgres' => 'pdo_pgsql']))->parse($url);
        $connection = DriverManager::getConnection($params);

        $this->em = new EntityManager($connection, $ormConfig);

        $tool = new SchemaTool($this->em);
        $tool->dropSchema([$this->em->getClassMetadata(Tenant::class)]);
        $tool->createSchema([$this->em->getClassMetadata(Tenant::class)]);

        $this->repository = new TenantRepository($this->em);
    }

    protected function tearDown(): void
    {
        $this->em->close();
    }

    public function testFindActiveByCodeReturnsNullForUnknownCode(): void
    {
        self::assertNull($this->repository->findActiveByCode('unknown'));
    }

    public function testFindActiveByCodeReturnsTenant(): void
    {
        $tenant = new Tenant('acme', 'Acme Corp');
        $this->em->persist($tenant);
        $this->em->flush();
        $this->em->clear();

        $found = $this->repository->findActiveByCode('acme');

        self::assertNotNull($found);
        self::assertSame('acme', $found->getCode());
        self::assertSame('Acme Corp', $found->getName());
    }

    public function testFindActiveByCodeIgnoresSoftDeletedTenants(): void
    {
        $tenant = new Tenant('deleted', 'To Delete');
        $this->em->persist($tenant);
        $this->em->flush();

        $tenant->softDelete();
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->repository->findActiveByCode('deleted'));
    }

    public function testFindAllActiveExcludesDeletedTenants(): void
    {
        $active = new Tenant('active', 'Active');
        $deleted = new Tenant('gone', 'Gone');

        $this->em->persist($active);
        $this->em->persist($deleted);
        $this->em->flush();

        $deleted->softDelete();
        $this->em->flush();
        $this->em->clear();

        $results = $this->repository->findAllActive();

        self::assertCount(1, $results);
        self::assertSame('active', $results[0]->getCode());
    }
}
