<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Tests\Unit\EventSubscriber;

use Maa\TenantBundle\Context\TenantContext;
use Maa\TenantBundle\Entity\Tenant;
use Maa\TenantBundle\EventSubscriber\TenantRequestSubscriber;
use Maa\TenantBundle\Exception\TenantNotFoundException;
use Maa\TenantBundle\Exception\TenantNotResolvedException;
use Maa\TenantBundle\Repository\TenantRepository;
use Maa\TenantBundle\Resolver\TenantResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class TenantRequestSubscriberTest extends TestCase
{
    public function testSetsTenantContextOnSuccess(): void
    {
        $tenant = new Tenant('acme', 'Acme');
        $context = new TenantContext();

        $resolver = $this->mockResolver('acme');
        $repository = $this->mockRepository('acme', $tenant);

        $subscriber = new TenantRequestSubscriber($resolver, $repository, $context, true);
        $subscriber->onKernelRequest($this->makeEvent());

        self::assertTrue($context->hasTenant());
        self::assertSame($tenant, $context->getTenant());
    }

    public function testThrowsWhenRequiredAndCodeNotResolved(): void
    {
        $subscriber = new TenantRequestSubscriber(
            $this->mockResolver(null),
            $this->createMock(TenantRepository::class),
            new TenantContext(),
            true,
        );

        $this->expectException(TenantNotResolvedException::class);
        $subscriber->onKernelRequest($this->makeEvent());
    }

    public function testDoesNotThrowWhenNotRequiredAndCodeNotResolved(): void
    {
        $context = new TenantContext();
        $subscriber = new TenantRequestSubscriber(
            $this->mockResolver(null),
            $this->createMock(TenantRepository::class),
            $context,
            false,
        );

        $subscriber->onKernelRequest($this->makeEvent());

        self::assertFalse($context->hasTenant());
    }

    public function testThrowsWhenTenantNotFoundInRegistry(): void
    {
        $subscriber = new TenantRequestSubscriber(
            $this->mockResolver('unknown'),
            $this->mockRepository('unknown', null),
            new TenantContext(),
            true,
        );

        $this->expectException(TenantNotFoundException::class);
        $subscriber->onKernelRequest($this->makeEvent());
    }

    public function testIgnoresSubRequests(): void
    {
        $resolver = $this->createMock(TenantResolverInterface::class);
        $resolver->expects(self::never())->method('resolve');

        $subscriber = new TenantRequestSubscriber(
            $resolver,
            $this->createMock(TenantRepository::class),
            new TenantContext(),
            true,
        );

        $kernel = $this->createMock(KernelInterface::class);
        $event = new RequestEvent($kernel, new Request(), HttpKernelInterface::SUB_REQUEST);
        $subscriber->onKernelRequest($event);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function mockResolver(?string $returnCode): TenantResolverInterface
    {
        $mock = $this->createMock(TenantResolverInterface::class);
        $mock->method('resolve')->willReturn($returnCode);
        return $mock;
    }

    private function mockRepository(string $code, ?Tenant $tenant): TenantRepository
    {
        $mock = $this->createMock(TenantRepository::class);
        $mock->method('findActiveByCode')->with($code)->willReturn($tenant);
        return $mock;
    }

    private function makeEvent(): RequestEvent
    {
        $kernel = $this->createMock(KernelInterface::class);
        return new RequestEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST);
    }
}
