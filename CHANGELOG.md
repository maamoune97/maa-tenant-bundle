# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `TenantConnectionMiddleware` / `TenantDriver` — transparent DBAL-level database switching
- `SubdomainTenantResolver` — resolves tenant from subdomain (default)
- `HeaderTenantResolver` — resolves tenant from `X-Tenant-Code` header (opt-in)
- `ChainTenantResolver` — priority-ordered chain of HTTP resolvers
- `TenantResolverInterface` — contract for custom resolvers
- `TenantContext` / `TenantContextInterface` — holds the current tenant for the request lifecycle
- `TenantRequestSubscriber` — resolves and sets tenant on `KernelEvents::REQUEST`
- `TenantConsoleSubscriber` — injects `--tenant` option into configured CLI commands
- `maa:tenant:setup` command — creates/updates the `tenants` registry table
- `maa:tenant:create` command — creates a tenant and provisions its database
- `maa:tenant:delete` command — soft-deletes a tenant
- `Tenant` entity with UUID primary key and soft-delete support
- Full Symfony 6.4 and 7.x compatibility
- Full Doctrine DBAL 3.x and 4.x compatibility
