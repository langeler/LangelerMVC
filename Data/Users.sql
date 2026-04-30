-- LangelerMVC release schema reference
-- Generated from framework and first-party module migrations.
-- SQLite-compatible reference SQL; migrations remain the authoritative runtime source.
-- Do not store live credentials, secrets, or deployment-local data in Data/*.sql.

-- users
CREATE TABLE "users" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" VARCHAR(191) NOT NULL, "email" VARCHAR(191) NOT NULL, "password" VARCHAR(255) NOT NULL, "remember_token" VARCHAR(255) NULL, "email_verified_at" TEXT NULL, "otp_secret" TEXT NULL, "otp_recovery_codes" TEXT NULL, "otp_confirmed_at" TEXT NULL, "status" VARCHAR(50) NOT NULL DEFAULT 'active', "created_at" TEXT NULL, "updated_at" TEXT NULL, UNIQUE ("email"));

-- roles
CREATE TABLE "roles" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" VARCHAR(120) NOT NULL, "label" VARCHAR(191) NULL, "description" TEXT NULL, "created_at" TEXT NULL, "updated_at" TEXT NULL, UNIQUE ("name"));

-- permissions
CREATE TABLE "permissions" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" VARCHAR(120) NOT NULL, "label" VARCHAR(191) NULL, "description" TEXT NULL, "created_at" TEXT NULL, "updated_at" TEXT NULL, UNIQUE ("name"));

-- user_roles
CREATE TABLE "user_roles" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "user_id" BIGINT NOT NULL, "role_id" BIGINT NOT NULL, "created_at" TEXT NULL, "updated_at" TEXT NULL, UNIQUE ("user_id", "role_id"), FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE, FOREIGN KEY ("role_id") REFERENCES "roles"("id") ON DELETE CASCADE ON UPDATE CASCADE);

-- role_permissions
CREATE TABLE "role_permissions" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "role_id" BIGINT NOT NULL, "permission_id" BIGINT NOT NULL, "created_at" TEXT NULL, "updated_at" TEXT NULL, UNIQUE ("role_id", "permission_id"), FOREIGN KEY ("role_id") REFERENCES "roles"("id") ON DELETE CASCADE ON UPDATE CASCADE, FOREIGN KEY ("permission_id") REFERENCES "permissions"("id") ON DELETE CASCADE ON UPDATE CASCADE);

-- user_auth_tokens
CREATE TABLE "user_auth_tokens" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "user_id" BIGINT NOT NULL, "type" VARCHAR(100) NOT NULL, "token_hash" TEXT NOT NULL, "payload" TEXT NULL, "expires_at" TEXT NOT NULL, "used_at" TEXT NULL, "created_at" TEXT NULL, "updated_at" TEXT NULL, FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE);
CREATE INDEX "user_auth_tokens_user_id_type_idx" ON "user_auth_tokens" ("user_id", "type");

-- user_passkeys
CREATE TABLE "user_passkeys" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "user_id" BIGINT NOT NULL, "name" VARCHAR(191) NOT NULL, "credential_id" VARCHAR(255) NOT NULL, "source" TEXT NOT NULL, "transports" TEXT NULL, "aaguid" VARCHAR(64) NULL, "counter" INT NOT NULL DEFAULT 0, "backup_eligible" INTEGER NULL DEFAULT 0, "backup_status" INTEGER NULL DEFAULT 0, "last_used_at" TEXT NULL, "created_at" TEXT NULL, "updated_at" TEXT NULL, UNIQUE ("credential_id"), FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE);
CREATE INDEX "user_passkeys_user_id_idx" ON "user_passkeys" ("user_id");
