<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Maa\TenantBundle\Entity\Tenant;

/**
 * @extends EntityRepository<Tenant>
 *
 * Extends EntityRepository (not ServiceEntityRepository) so the class can be
 * instantiated directly in integration tests without a ManagerRegistry.
 * In the Symfony container it is wired explicitly to the maa_tenant EM.
 */
class TenantRepository extends EntityRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(Tenant::class));
    }

    public function findActiveByCode(string $code): ?Tenant
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.code = :code')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Tenant[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.deletedAt IS NULL')
            ->orderBy('t.code', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
