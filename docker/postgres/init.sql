-- Activate the uuid-ossp extension in the registry database.
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Pre-create a sample tenant database used in integration tests.
-- The bundle normally creates these on-demand via maa:tenant:create.
CREATE DATABASE tenant_test_acme OWNER tenant_user;
