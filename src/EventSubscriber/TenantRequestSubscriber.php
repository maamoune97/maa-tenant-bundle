<?php

declare(strict_types=1);

namespace Maa\TenantBundle\EventSubscriber;

use Maa\TenantBundle\Context\TenantContextInterface;
use Maa\TenantBundle\Exception\TenantNotFoundException;
use Maa\TenantBundle\Exception\TenantNotResolvedException;
use Maa\TenantBundle\Repository\TenantRepository;
use Maa\TenantBundle\Resolver\TenantResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Resolves the tenant early in the request lifecycle and stores it in TenantContext.
 *
 * Priority 100 ensures this runs before the security firewall (priority 8)
 * so that authenticated queries already target the correct tenant database.
 */
final class TenantRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TenantResolverInterface $resolver,
        private readonly TenantRepository $repository,
        private readonly TenantContextInterface $context,
        private readonly bool $required,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $code = $this->resolver->resolve($request);

        if ($code === null) {
            if ($this->required) {
                throw new TenantNotResolvedException();
            }
            return;
        }

        $tenant = $this->repository->findActiveByCode($code);

        if ($tenant === null) {
            throw new TenantNotFoundException($code);
        }

        $this->context->setTenant($tenant);
    }
}
