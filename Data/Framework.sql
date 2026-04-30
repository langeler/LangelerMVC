-- LangelerMVC release schema reference
-- Generated from framework and first-party module migrations.
-- SQLite-compatible reference SQL; migrations remain the authoritative runtime source.
-- Do not store live credentials, secrets, or deployment-local data in Data/*.sql.

-- framework_migrations
CREATE TABLE "framework_migrations" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "migration" TEXT NOT NULL UNIQUE, "module" TEXT NOT NULL, "class" TEXT NOT NULL, "batch" INTEGER NOT NULL, "ran_at" TEXT NOT NULL);

-- framework_migration_locks
CREATE TABLE "framework_migration_locks" ("name" TEXT PRIMARY KEY, "owner" TEXT NOT NULL, "acquired_at" INTEGER NOT NULL);

-- framework_jobs
CREATE TABLE "framework_jobs" ("id" VARCHAR(64) NOT NULL, "queue" VARCHAR(120) NOT NULL, "type" VARCHAR(60) NOT NULL, "class" VARCHAR(255) NOT NULL, "handler" TEXT NULL, "payload" TEXT NOT NULL, "attempts" INT NOT NULL DEFAULT 0, "available_at" INT NOT NULL, "reserved_at" INT NULL, "created_at" INT NOT NULL, PRIMARY KEY ("id"));
CREATE INDEX "framework_jobs_available_at_idx" ON "framework_jobs" ("available_at");
CREATE INDEX "framework_jobs_created_at_idx" ON "framework_jobs" ("created_at");
CREATE INDEX "framework_jobs_queue_idx" ON "framework_jobs" ("queue");
CREATE INDEX "framework_jobs_reserved_at_idx" ON "framework_jobs" ("reserved_at");

-- framework_failed_jobs
CREATE TABLE "framework_failed_jobs" ("id" VARCHAR(64) NOT NULL, "queue" VARCHAR(120) NOT NULL, "type" VARCHAR(60) NOT NULL, "class" VARCHAR(255) NOT NULL, "handler" TEXT NULL, "payload" TEXT NOT NULL, "attempts" INT NOT NULL DEFAULT 0, "exception" TEXT NOT NULL, "failed_at" INT NOT NULL, PRIMARY KEY ("id"));
CREATE INDEX "framework_failed_jobs_failed_at_idx" ON "framework_failed_jobs" ("failed_at");
CREATE INDEX "framework_failed_jobs_queue_idx" ON "framework_failed_jobs" ("queue");

-- framework_audit_log
CREATE TABLE "framework_audit_log" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "category" VARCHAR(120) NOT NULL, "event" VARCHAR(255) NOT NULL, "severity" VARCHAR(30) NOT NULL, "actor_type" VARCHAR(255) NULL, "actor_id" VARCHAR(255) NULL, "context" TEXT NOT NULL, "created_at" INT NOT NULL);
CREATE INDEX "framework_audit_log_category_idx" ON "framework_audit_log" ("category");
CREATE INDEX "framework_audit_log_created_at_idx" ON "framework_audit_log" ("created_at");
