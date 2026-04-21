<?php

declare(strict_types=1);

namespace Maa\TenantBundle\EventSubscriber;

use Maa\TenantBundle\Context\TenantContextInterface;
use Maa\TenantBundle\Exception\TenantNotFoundException;
use Maa\TenantBundle\Repository\TenantRepository;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\ConsoleEvents;

/**
 * Injects the --tenant option into configured commands and resolves the tenant before execution.
 *
 * How the option injection works
 * ──────────────────────────────
 * Symfony's Application::doRunCommand() wraps input binding in a try/catch that silently
 * discards binding errors, so "--tenant" tokens survive even though the option is not yet
 * declared. ConsoleEvents::COMMAND fires after that binding attempt but BEFORE Command::run()
 * calls $input->validate(). We add the option to the definition here and rebind, which
 * re-parses the original ArgvInput tokens with the now-complete definition.
 */
final class TenantConsoleSubscriber implements EventSubscriberInterface
{
    /**
     * @param string[] $tenantCommands Command names that require --tenant.
     */
    public function __construct(
        private readonly TenantContextInterface $context,
        private readonly TenantRepository $repository,
        private readonly array $tenantCommands,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['onConsoleCommand', 128],
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if ($command === null || !in_array($command->getName(), $this->tenantCommands, true)) {
            return;
        }

        $input = $event->getInput();

        if (!$command->getDefinition()->hasOption('tenant')) {
            $command->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'Tenant code');
            // Rebind so the raw argv tokens are re-parsed with the updated definition.
            $input->bind($command->getDefinition());
        }

        $code = $input->getOption('tenant');

        if (!is_string($code) || $code === '') {
            throw new \InvalidArgumentException(
                sprintf('The command "%s" requires the --tenant option.', $command->getName())
            );
        }

        $tenant = $this->repository->findActiveByCode($code);

        if ($tenant === null) {
            throw new TenantNotFoundException($code);
        }

        $this->context->setTenant($tenant);
    }
}