<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Tests\Unit\Resolver;

use Maa\TenantBundle\Resolver\ChainTenantResolver;
use Maa\TenantBundle\Resolver\TenantResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ChainTenantResolverTest extends TestCase
{
    public function testReturnsNullWhenChainIsEmpty(): void
    {
        $chain = new ChainTenantResolver([]);
        self::assertNull($chain->resolve(new Request()));
    }

    public function testReturnsFirstNonNullResult(): void
    {
        $first = $this->makeResolver(null);
        $second = $this->makeResolver('acme');
        $third = $this->makeResolver('other');

        $chain = new ChainTenantResolver([$first, $second, $third]);

        self::assertSame('acme', $chain->resolve(new Request()));
    }

    public function testSkipsNullResultsAndContinues(): void
    {
        $first = $this->makeResolver(null);
        $second = $this->makeResolver(null);
        $third = $this->makeResolver('beta');

        $chain = new ChainTenantResolver([$first, $second, $third]);

        self::assertSame('beta', $chain->resolve(new Request()));
    }

    public function testReturnsNullWhenAllResolversReturnNull(): void
    {
        $chain = new ChainTenantResolver([
            $this->makeResolver(null),
            $this->makeResolver(null),
        ]);

        self::assertNull($chain->resolve(new Request()));
    }

    private function makeResolver(?string $returnValue): TenantResolverInterface
    {
        $mock = $this->createMock(TenantResolverInterface::class);
        $mock->method('resolve')->willReturn($returnValue);
        return $mock;
    }
}
