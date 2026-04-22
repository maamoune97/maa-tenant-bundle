# MaaTenantBundle

Symfony bundle for transparent multi-tenancy with per-tenant databases.

Each tenant gets its own PostgreSQL database. Resolution happens at the DBAL level — your application code injects a single `EntityManagerInterface` and the bundle silently switches the connection to the right database on every request or CLI command.

## Requirements

- PHP 8.2+
- Symfony 6.4 or 7.x
- Doctrine ORM 2.17 / 3.x + DBAL 3.7 / 4.x
- PostgreSQL (one registry database + one database per tenant)

## Installation

```bash
composer require maamoune97/maa-tenant-bundle
```

Register the bundle in `config/bundles.php`:

```php
Maa\TenantBundle\MaaTenantBundle::class => ['all' => true],
```

## Configuration

```yaml
# config/packages/maa_tenant.yaml
maa_tenant:
    registry_url: '%env(TENANT_REGISTRY_DATABASE_URL)%'
    tenant_connection: default       # Doctrine connection to intercept
    tenant_db_prefix: 'tenant_'     # database name = prefix + tenant code
    http:
        required: true              # 404 when no tenant is resolved on HTTP
    cli:
        tenant_commands:
            - 'doctrine:migrations:migrate'
            - 'app:my-command'
```

```dotenv
TENANT_REGISTRY_DATABASE_URL=postgresql://user:pass@localhost:5432/tenant_registry
DATABASE_URL=postgresql://user:pass@localhost:5432/placeholder
```

> `DATABASE_URL` can point to any database — the DBAL middleware replaces `dbname`
> with the resolved tenant's database before the connection is opened.

## Setup

```bash
# Create (or update) the `tenants` table in the registry database
bin/console maa:tenant:setup --force
```

## Managing tenants

```bash
# Create a tenant and provision its database
bin/console maa:tenant:create acme "Acme Corporation"

# Create without provisioning the database
bin/console maa:tenant:create acme "Acme Corporation" --skip-db

# Soft-delete a tenant
bin/console maa:tenant:delete --code=acme
```

## Tenant resolution

### HTTP — subdomain (default)

`acme.example.com` → code `acme` → database `tenant_acme`

### HTTP — custom header (opt-in)

Register `HeaderTenantResolver` as a tagged service:

```yaml
# config/services.yaml
Maa\TenantBundle\Resolver\Http\HeaderTenantResolver:
    tags:
        - { name: maa_tenant.http_resolver, priority: 10 }
```

`X-Tenant-Code: acme` → code `acme`

### HTTP — query parameter (local dev, opt-in)

For fullstack Symfony apps in local development where subdomains are not available,
enable `QueryParamTenantResolver` only in the `dev` environment:

```yaml
# config/services_dev.yaml
Maa\TenantBundle\Resolver\Http\QueryParamTenantResolver:
    tags:
        - { name: maa_tenant.http_resolver, priority: 20 }
```

You can then navigate directly to any page with `?_tenant=acme` in the URL:

```
http://localhost:8000/dashboard?_tenant=acme
http://localhost:8000/invoices?_tenant=beta
```

The query parameter takes priority over the subdomain in dev, and is completely
absent in production (never registered as a resolver).

### HTTP — custom resolver

Implement `TenantResolverInterface` and tag your service:

```php
use Maa\TenantBundle\Resolver\TenantResolverInterface;
use Symfony\Component\HttpFoundation\Request;

#[AutoconfigureTag('maa_tenant.http_resolver', ['priority' => 20])]
final class JwtTenantResolver implements TenantResolverInterface
{
    public function resolve(Request $request): ?string
    {
        // Extract the tenant code from a JWT, cookie, etc.
        return null;
    }
}
```

### CLI

Commands listed under `cli.tenant_commands` automatically receive a `--tenant` option:

```bash
bin/console doctrine:migrations:migrate --tenant=acme
bin/console app:my-command --tenant=beta
```

## Accessing the current tenant

```php
use Maa\TenantBundle\Context\TenantContextInterface;

final class MyService
{
    public function __construct(
        private readonly TenantContextInterface $tenantContext,
    ) {}

    public function doSomething(): void
    {
        $tenant = $this->tenantContext->getTenant(); // null outside a tenant context
    }
}
```

## Architecture

```
HTTP request (acme.example.com)
        │
        ▼  priority=100
TenantRequestSubscriber
        │  SubdomainTenantResolver → "acme"
        │  TenantRepository::findActiveByCode("acme")
        ▼
TenantContext::setTenant($tenant)
        │
        ▼
Doctrine DBAL (default connection)
        │  TenantConnectionMiddleware::wrap()
        │  TenantDriver::connect() → params['dbname'] = "tenant_acme"
        ▼
PostgreSQL database: tenant_acme
```

The bundle uses two entity managers internally:

| EM | Connection | Content |
|---|---|---|
| `maa_tenant` | `maa_tenant_registry` | `Tenant` entity — internal only |
| `default` (app) | `DATABASE_URL` + middleware | App entities — routed to `tenant_acme` |

Your application always injects `EntityManagerInterface` and gets the `default` EM. No `--em=` flag needed, no second entity manager to inject.

## License

MIT — see [LICENSE](LICENSE).
