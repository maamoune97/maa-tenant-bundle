<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Command;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Maa\TenantBundle\Entity\Tenant;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'maa:tenant:setup',
    description: 'Create (or update) the tenant registry schema in the registry database.',
)]
final class TenantSetupCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $registryEm)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Apply the schema changes (dry-run by default)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');

        $conn = $this->registryEm->getConnection();

        $this->createDatabaseIfNotExists($conn, $io);

        $platform = $conn->getDatabasePlatform();
        $schemaManager = $conn->createSchemaManager();

        // Build the target schema from the Tenant entity only.
        $tool = new SchemaTool($this->registryEm);
        $classes = [$this->registryEm->getClassMetadata(Tenant::class)];
        $targetSchema = $tool->getSchemaFromMetadata($classes);

        // Build the current schema scoped to only the tables we own,
        // so the comparator never generates DROP statements for foreign tables.
        $introspected = $schemaManager->introspectSchema();
        $ownedTableNames = array_map(
            static fn ($t) => $t->getName(),
            $targetSchema->getTables()
        );
        $currentSchema = new \Doctrine\DBAL\Schema\Schema(
            array_filter(
                $introspected->getTables(),
                static fn ($t) => in_array($t->getName(), $ownedTableNames, true)
            )
        );

        $comparator = new Comparator($platform);
        $diff = $comparator->compareSchemas($currentSchema, $targetSchema);
        $sqls = $platform->getAlterSchemaSQL($diff);

        if (empty($sqls)) {
            $io->success('Registry schema is already up to date.');
            return Command::SUCCESS;
        }

        $io->listing($sqls);

        if (!$force) {
            $io->note('Dry-run mode. Pass --force to apply the changes.');
            return Command::SUCCESS;
        }

        foreach ($sqls as $sql) {
            $conn->executeStatement($sql);
        }

        $io->success('Registry schema updated.');

        return Command::SUCCESS;
    }

    private function createDatabaseIfNotExists(\Doctrine\DBAL\Connection $conn, SymfonyStyle $io): void
    {
        $params = $conn->getParams();
        $dbName = $params['dbname'] ?? null;

        if (!$dbName) {
            return;
        }

        $adminParams = $params;
        unset($adminParams['url'], $adminParams['dbname']);
        $adminParams['dbname'] = 'postgres';

        $adminConn = DriverManager::getConnection($adminParams);

        try {
            $exists = (bool) $adminConn->fetchOne(
                'SELECT 1 FROM pg_database WHERE datname = :name',
                ['name' => $dbName]
            );

            if (!$exists) {
                $adminConn->executeStatement('CREATE DATABASE ' . $adminConn->quoteIdentifier($dbName));
                $io->success("Database \"$dbName\" created.");
            }
        } finally {
            $adminConn->close();
        }
    }
}
