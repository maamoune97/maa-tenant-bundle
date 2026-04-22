# MaaTenantBundle — Guide d'intégration

## Installation

```bash
composer require maamoune97/maa-tenant-bundle
```

Enregistrer le bundle dans `config/bundles.php` :

```php
Maa\TenantBundle\MaaTenantBundle::class => ['all' => true],
```

---

## Configuration minimale

```yaml
# config/packages/maa_tenant.yaml
maa_tenant:
    registry_url: '%env(TENANT_REGISTRY_DATABASE_URL)%'
    tenant_connection: default        # connexion Doctrine à intercepter
    tenant_db_prefix: 'tenant_'      # préfixe des bases de données tenants
    http:
        required: true               # 404 si aucun tenant résolu sur HTTP
    cli:
        tenant_commands:
            - 'doctrine:migrations:migrate'
            - 'app:import-data'
```

```dotenv
# .env
TENANT_REGISTRY_DATABASE_URL=postgresql://user:pass@localhost:5432/tenant_registry
DATABASE_URL=postgresql://user:pass@localhost:5432/placeholder
```

> `DATABASE_URL` peut pointer vers n'importe quelle base : le middleware DBAL remplace
> automatiquement `dbname` par celui du tenant résolu avant l'ouverture de la connexion.

---

## Initialisation du registre

```bash
# Créer (ou mettre à jour) la table `tenants` dans la base de registre
bin/console maa:tenant:setup --force
```

---

## Gestion des tenants

```bash
# Créer un tenant (+ sa base de données)
bin/console maa:tenant:create acme "Acme Corporation"

# Créer sans provisioner la base
bin/console maa:tenant:create acme "Acme Corporation" --skip-db

# Supprimer (soft-delete) un tenant
bin/console maa:tenant:delete --code=acme
```

---

## Résolution du tenant

### HTTP — sous-domaine (par défaut)

`acme.example.com` → code `acme` → base `tenant_acme`.

### HTTP — header personnalisé (opt-in)

```yaml
# config/services.yaml
Maa\TenantBundle\Resolver\Http\HeaderTenantResolver:
    tags:
        - { name: maa_tenant.http_resolver, priority: 10 }
```

`X-Tenant-Code: acme` → code `acme`.

### HTTP — résolveur personnalisé

```php
use Maa\TenantBundle\Resolver\TenantResolverInterface;
use Symfony\Component\HttpFoundation\Request;

#[AutoconfigureTag('maa_tenant.http_resolver', ['priority' => 20])]
final class JwtTenantResolver implements TenantResolverInterface
{
    public function resolve(Request $request): ?string
    {
        // Extraire le tenant depuis le JWT, un cookie, etc.
        return null;
    }
}
```

### CLI

Les commandes déclarées dans `cli.tenant_commands` reçoivent automatiquement l'option `--tenant` :

```bash
bin/console doctrine:migrations:migrate --tenant=acme
bin/console app:import-data --tenant=beta
```

---

## Migrations par tenant

```bash
# Appliquer les migrations sur la base d'un tenant — aucun --em requis
bin/console doctrine:migrations:migrate --tenant=acme
```

Ajoutez `doctrine:migrations:migrate` à `cli.tenant_commands` dans votre config :

```yaml
maa_tenant:
    cli:
        tenant_commands:
            - 'doctrine:migrations:migrate'
            - 'doctrine:schema:update'
```

---

## Les apps clientes n'ont pas besoin de gérer plusieurs entity managers

Le bundle utilise **deux entity managers** en interne :

| EM | Connexion | Contenu |
|---|---|---|
| `maa_tenant` | `maa_tenant_registry` | Entité `Tenant` uniquement — usage interne au bundle |
| `default` (app) | `DATABASE_URL` + middleware | Entités de l'app — redirigé automatiquement vers `tenant_acme` |

**L'app injecte toujours `EntityManagerInterface` et obtient le `default` EM.**
Elle n'a jamais besoin de spécifier `--em=` ni d'injecter `doctrine.orm.maa_tenant_entity_manager`.

La séparation des mappings (`auto_mapping`) est gérée automatiquement par le bundle :
l'entité `Tenant` est attachée au `maa_tenant` EM via `prepend()`, ce qui empêche
DoctrineBundle de l'inclure dans le `default` EM.

---

## Accéder au tenant courant dans vos services

```php
use Maa\TenantBundle\Context\TenantContextInterface;

final class MyService
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function doSomething(): void
    {
        $tenant = $this->tenantContext->getTenant(); // peut être null hors contexte tenant
    }
}
```

---

## Schéma d'architecture

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
