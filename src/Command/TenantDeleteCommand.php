<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Maa\TenantBundle\Event\TenantDeletedEvent;
use Maa\TenantBundle\Repository\TenantRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'maa:tenant:delete',
    description: 'Soft-delete a tenant (marks it as deleted; does NOT drop the database).',
)]
final class TenantDeleteCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $registryEm,
        private readonly TenantRepository $tenantRepository,
        private readonly string $dbPrefix,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('code', null, InputOption::VALUE_REQUIRED, 'Code of the tenant to delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $code = $input->getOption('code');
        if (!is_string($code) || $code === '') {
            $io->error('Provide the tenant code via --code.');
            return Command::FAILURE;
        }

        $tenant = $this->tenantRepository->findActiveByCode($code);

        if ($tenant === null) {
            $io->error(sprintf('No active tenant found with code "%s".', $code));
            return Command::FAILURE;
        }

        if (!$io->confirm(sprintf('Soft-delete tenant "%s" (%s)? The database will NOT be dropped.', $tenant->getName(), $code), false)) {
            $io->note('Aborted.');
            return Command::SUCCESS;
        }

        $tenant->softDelete();
        $this->registryEm->flush();

        $this->eventDispatcher->dispatch(new TenantDeletedEvent($tenant, $tenant->getDatabaseName($this->dbPrefix)));

        $io->success(sprintf('Tenant "%s" has been soft-deleted.', $code));
        $io->note(sprintf('The database "%s" still exists. Drop it manually if no longer needed.', $tenant->getDatabaseName($this->dbPrefix)));

        return Command::SUCCESS;
    }
}
