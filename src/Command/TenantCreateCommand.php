<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Maa\TenantBundle\Entity\Tenant;
use Maa\TenantBundle\Repository\TenantRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'maa:tenant:create',
    description: 'Create a new tenant and provision its dedicated database.',
)]
final class TenantCreateCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $registryEm,
        private readonly TenantRepository $tenantRepository,
        private readonly Connection $registryConnection,
        private readonly string $dbPrefix,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('code', InputArgument::REQUIRED, 'Unique slug used as subdomain and DB suffix (e.g. "acme")')
            ->addArgument('name', InputArgument::REQUIRED, 'Human-readable tenant name')
            ->addOption('skip-db', null, InputOption::VALUE_NONE, 'Register the tenant without creating the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $code */
        $code = $input->getArgument('code');
        /** @var string $name */
        $name = $input->getArgument('name');

        if (!preg_match('/^[a-z0-9][a-z0-9\-]{0,61}[a-z0-9]$/i', $code)) {
            $io->error('The code must be a valid DNS label (letters, digits, hyphens; no leading/trailing hyphen).');
            return Command::FAILURE;
        }

        if ($this->tenantRepository->findActiveByCode($code) !== null) {
            $io->error(sprintf('A tenant with code "%s" already exists.', $code));
            return Command::FAILURE;
        }

        $tenant = new Tenant($code, $name);
        $this->registryEm->persist($tenant);
        $this->registryEm->flush();

        $io->success(sprintf('Tenant "%s" (%s) registered with id %s.', $code, $name, $tenant->getId()));

        if (!$input->getOption('skip-db')) {
            $dbName = $tenant->getDatabaseName($this->dbPrefix);
            $this->createDatabase($dbName);
            $io->success(sprintf('Database "%s" created.', $dbName));
            $io->note('Run your migrations for this tenant: bin/console doctrine:migrations:migrate --em=default --tenant=' . $code);
        }

        return Command::SUCCESS;
    }

    private function createDatabase(string $dbName): void
    {
        // Connect to the default postgres maintenance database to issue CREATE DATABASE.
        $params = $this->registryConnection->getParams();
        $params['dbname'] = 'postgres';

        $adminConnection = \Doctrine\DBAL\DriverManager::getConnection($params);

        try {
            $adminConnection->executeStatement(
                sprintf('CREATE DATABASE %s', $adminConnection->quoteIdentifier($dbName))
            );
        } finally {
            $adminConnection->close();
        }
    }
}
