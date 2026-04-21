<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TenantNotResolvedException extends NotFoundHttpException
{
    public function __construct(string $message = 'Could not determine tenant from request.', ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
