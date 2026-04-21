<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Maa\TenantBundle\Repository\TenantRepository;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
#[ORM\Table(name: 'tenants')]
#[ORM\UniqueConstraint(name: 'tenants_code_unique', columns: ['code'])]
class Tenant
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 63)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct(string $code, string $name)
    {
        $this->id = Uuid::v4();
        $this->code = $code;
        $this->name = $name;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function getDatabaseName(string $prefix = 'tenant_'): string
    {
        return $prefix . $this->code;
    }
}
