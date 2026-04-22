<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Command;

use Doctrine\DBAL\Connection;
use Maa\TenantBundle\Context\TenantContextInterface;
use Maa\TenantBundle\Repository\TenantRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'maa:tenant:foreach',
    description: 'Run a command for every active tenant.',
)]
final class TenantForeachCommand extends Command
{
    public function __construct(
        private readonly TenantRepository $tenantRepository,
        private readonly TenantContextInterface $tenantContext,
        private readonly Connection $defaultConnection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('command-name', InputArgument::REQUIRED, 'Command to execute for each tenant')
            ->addArgument('command-args', InputArgument::IS_ARRAY, 'Extra arguments/options forwarded to the command (use -- to separate)')
            ->addOption('tenants', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of tenant codes to include (default: all active)')
            ->addOption('exclude', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of tenant codes to skip')
            ->addOption('continue-on-error', 'c', InputOption::VALUE_NONE, 'Continue to the next tenant even if the command fails');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tenants = $this->tenantRepository->findAllActive();

        if (empty($tenants)) {
            $io->warning('No active tenants found.');
            return Command::SUCCESS;
        }

        // Apply --tenants filter.
        if ($only = $input->getOption('tenants')) {
            $allowed = array_map('trim', explode(',', $only));
            $tenants = array_filter($tenants, static fn ($t) => in_array($t->getCode(), $allowed, true));
        }

        // Apply --exclude filter.
        if ($excluded = $input->getOption('exclude')) {
            $skip = array_map('trim', explode(',', $excluded));
            $tenants = array_filter($tenants, static fn ($t) => !in_array($t->getCode(), $skip, true));
        }

        $tenants = array_values($tenants);

        if (empty($tenants)) {
            $io->warning('No tenants match the given filters.');
            return Command::SUCCESS;
        }

        $commandName = $input->getArgument('command-name');
        $extraArgs   = $this->parseExtraArgs((array) $input->getArgument('command-args'));
        $continueOnError = (bool) $input->getOption('continue-on-error');

        $subCommand = $this->getApplication()->find($commandName);

        $io->title(sprintf('Running "%s" for %d tenant(s)', $commandName, count($tenants)));

        $succeeded = 0;
        $failed    = 0;

        foreach ($tenants as $tenant) {
            $io->section(sprintf('[%s] %s', $tenant->getCode(), $tenant->getName()));

            // Close the connection so the DBAL middleware reconnects to the tenant's DB.
            $this->defaultConnection->close();
            $this->tenantContext->setTenant($tenant);

            $subInput = new ArrayInput(array_merge(['command' => $commandName], $extraArgs));
            $subInput->setInteractive(false);

            $exitCode = $subCommand->run($subInput, $output);

            if ($exitCode === Command::SUCCESS) {
                $io->writeln(sprintf('<info>✓ %s — OK</info>', $tenant->getCode()));
                $succeeded++;
            } else {
                $io->writeln(sprintf('<error>✗ %s — FAILED (exit %d)</error>', $tenant->getCode(), $exitCode));
                $failed++;

                if (!$continueOnError) {
                    $io->error('Stopped. Pass --continue-on-error (-c) to process remaining tenants.');
                    return Command::FAILURE;
                }
            }
        }

        if ($failed > 0) {
            $io->error(sprintf('Completed with errors — %d succeeded, %d failed.', $succeeded, $failed));
            return Command::FAILURE;
        }

        $io->success(sprintf('All %d tenant(s) processed successfully.', $succeeded));
        return Command::SUCCESS;
    }

    /** Converts ["--foo=bar", "--flag", "arg"] into an ArrayInput-compatible array. */
    private function parseExtraArgs(array $args): array
    {
        $parsed = [];
        foreach ($args as $arg) {
            if (str_contains($arg, '=')) {
                [$key, $value] = explode('=', $arg, 2);
                $parsed[$key] = $value;
            } elseif (str_starts_with($arg, '--')) {
                $parsed[$arg] = true;
            } else {
                $parsed[] = $arg;
            }
        }
        return $parsed;
    }
}
