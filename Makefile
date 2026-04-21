.DEFAULT_GOAL := help
COMPOSE        := docker compose
PHP            := $(COMPOSE) exec php

##
## ── Setup ────────────────────────────────────────────────────────────────────
##

.PHONY: build
build: ## Build the PHP image
	$(COMPOSE) build --no-cache php

.PHONY: up
up: ## Start all services in the background
	$(COMPOSE) up -d

.PHONY: down
down: ## Stop and remove containers (volumes are preserved)
	$(COMPOSE) down

.PHONY: install
install: up ## Install Composer dependencies
	$(PHP) composer install

##
## ── Tests ─────────────────────────────────────────────────────────────────────
##

.PHONY: test
test: up ## Run the full test suite
	$(PHP) php vendor/bin/phpunit

.PHONY: test-unit
test-unit: up ## Run unit tests only
	$(PHP) php vendor/bin/phpunit --testsuite Unit

.PHONY: test-coverage
test-coverage: up ## Generate HTML coverage report in var/coverage/
	$(PHP) php -d xdebug.mode=coverage vendor/bin/phpunit --coverage-html var/coverage

##
## ── Quality ───────────────────────────────────────────────────────────────────
##

.PHONY: phpstan
phpstan: up ## Run PHPStan static analysis
	$(PHP) php vendor/bin/phpstan analyse src tests --level 8

##
## ── Shell ─────────────────────────────────────────────────────────────────────
##

.PHONY: shell
shell: up ## Open a shell inside the PHP container
	$(COMPOSE) exec php sh

.PHONY: psql
psql: up ## Open a psql session in the registry database
	$(COMPOSE) exec postgres psql -U tenant_user -d tenant_registry

##
## ── Misc ──────────────────────────────────────────────────────────────────────
##

.PHONY: logs
logs: ## Tail container logs
	$(COMPOSE) logs -f

.PHONY: help
help: ## Show this help
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-20s\033[0m %s\n", $$1, $$2}' \
		| sed -e 's/\[32m## /[33m/'
