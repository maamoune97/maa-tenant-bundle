<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Exception;

final class TenantNotFoundException extends \RuntimeException
{
    public function __construct(string $code, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Tenant with code "%s" was not found or has been deleted.', $code), 0, $previous);
    }
}