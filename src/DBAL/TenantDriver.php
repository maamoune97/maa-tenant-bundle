<?php

declare(strict_types=1);

namespace Maa\TenantBundle\DBAL;

use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Maa\TenantBundle\Context\TenantContextInterface;

/**
 * Overrides the `dbname` connection parameter with the current tenant's database
 * immediately before DBAL opens the underlying PDO/pgsql connection.
 */
final class TenantDriver extends AbstractDriverMiddleware
{
    public function __construct(
        \Doctrine\DBAL\Driver $driver,
        private readonly TenantContextInterface $context,
        private readonly string $dbPrefix,
    ) {
        parent::__construct($driver);
    }

    public function connect(#[\SensitiveParameter] array $params): DriverConnection
    {
        $tenant = $this->context->getTenant();

        if ($tenant !== null) {
            $params['dbname'] = $tenant->getDatabaseName($this->dbPrefix);
        }

        return parent::connect($params);
    }
}
