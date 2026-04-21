<?php

declare(strict_types=1);

namespace Maa\TenantBundle\Tests\Unit\Resolver\Http;

use Maa\TenantBundle\Resolver\Http\SubdomainTenantResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class SubdomainTenantResolverTest extends TestCase
{
    private SubdomainTenantResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new SubdomainTenantResolver();
    }

    public function testExtractsTenantFromSubdomain(): void
    {
        $request = Request::create('https://acme.example.com/path');
        self::assertSame('acme', $this->resolver->resolve($request));
    }

    public function testReturnsNullForBareHost(): void
    {
        $request = Request::create('https://example.com/path');
        self::assertNull($this->resolver->resolve($request));
    }

    public function testReturnsNullForIgnoredSubdomain(): void
    {
        $request = Request::create('https://www.example.com/path');
        self::assertNull($this->resolver->resolve($request));
    }

    public function testCustomIgnoredSubdomains(): void
    {
        $resolver = new SubdomainTenantResolver(['www', 'api', 'admin']);

        self::assertNull($resolver->resolve(Request::create('https://admin.example.com/')));
        self::assertSame('acme', $resolver->resolve(Request::create('https://acme.example.com/')));
    }

    public function testExtractsTenantFromDeepSubdomain(): void
    {
        // Takes the leftmost segment only.
        $request = Request::create('https://tenant1.app.example.com/');
        self::assertSame('tenant1', $this->resolver->resolve($request));
    }

    public function testReturnsNullForLocalhostWithoutSubdomain(): void
    {
        $request = Request::create('http://localhost/');
        self::assertNull($this->resolver->resolve($request));
    }
}
