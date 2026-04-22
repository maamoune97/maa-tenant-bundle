# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-04-22

### Added
- `maa:tenant:foreach` command — runs any console command for every active tenant in sequence.
  Supports `--tenants=a,b` (include filter), `--exclude=x` (skip filter), and
  `--continue-on-error` (`-c`). Each tenant gets a fresh DBAL connection before its run.
- `maa:tenant:create --migrate` option — automatically runs `doctrine:migrations:migrate`
  after provisioning the new database, routing the connection to the correct tenant DB.

### Fixed
- **`TenantNotFoundException` returned HTTP 500.** Changed to extend `NotFoundHttpException`
  so Symfony maps it to a 404, consistent with `TenantNotResolvedException`.
- **CLI commands routed to wrong database.** `TenantConsoleSubscriber` now closes the default
  DBAL connection after setting the tenant context, forcing DBAL to reconnect via the
  middleware on the command's first query. Without this, a connection already open (pointing
  to the default DB) was reused and migrations/queries ran against the wrong database.
- **`maa:tenant:delete` note showed wrong database name.** `getDatabaseName()` was called
  without the configured prefix, defaulting to `tenant_` instead of the app-configured value.

## [1.1.0] - 2026-04-22

### Added
- `SessionQueryParamTenantResolver` — dev resolver that stores the tenant in the session so
  navigation works without repeating `?_tenant=` on every request. Replaces the stateless
  `QueryParamTenantResolver` in `services_dev.yaml`.

### Fixed
- **`maa:tenant:setup --force` was silently dropping unrelated tables.** Doctrine ORM 3.x
  removed the `$saveMode` parameter from `SchemaTool::updateSchema()`, making it always
  destructive. The command now builds a scoped schema diff limited to the bundle's own tables
  so it never generates `DROP` statements for tables it does not own.
- **DBAL middleware not applied.** `MaaTenantExtension::prepend()` was injecting
  `connections.<name>.middlewares: [...]` into the Doctrine config, but DoctrineBundle has no
  such config node — the option was silently ignored. The middleware is now tagged with
  `doctrine.middleware` (connection-scoped) directly in `load()`, which is the correct
  DoctrineBundle API.

## [1.0.0] - 2026-04-22

### Added
- `TenantConnectionMiddleware` / `TenantDriver` — transparent DBAL-level database switching
- `SubdomainTenantResolver` — resolves tenant from subdomain (default)
- `HeaderTenantResolver` — resolves tenant from `X-Tenant-Code` header (opt-in)
- `QueryParamTenantResolver` — resolves tenant from `?_tenant=` query parameter (opt-in, dev)
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
