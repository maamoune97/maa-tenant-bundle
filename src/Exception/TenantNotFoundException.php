<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TenantNotFoundException extends NotFoundHttpException
{
    public function __construct(string $code, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Tenant with code "%s" was not found or has been deleted.', $code), $previous);
    }
}