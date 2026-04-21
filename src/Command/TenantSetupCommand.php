<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Command;

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

        $tool = new SchemaTool($this->registryEm);
        $classes = [$this->registryEm->getClassMetadata(Tenant::class)];
        $sqls = $tool->getUpdateSchemaSql($classes, true);

        if (empty($sqls)) {
            $io->success('Registry schema is already up to date.');
            return Command::SUCCESS;
        }

        $io->listing($sqls);

        if (!$force) {
            $io->note('Dry-run mode. Pass --force to apply the changes.');
            return Command::SUCCESS;
        }

        $tool->updateSchema($classes, true);
        $io->success('Registry schema updated.');

        return Command::SUCCESS;
    }
}
