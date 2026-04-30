-- LangelerMVC release schema reference
-- Generated from framework and first-party module migrations.
-- SQLite-compatible reference SQL; migrations remain the authoritative runtime source.
-- Do not store live credentials, secrets, or deployment-local data in Data/*.sql.

-- pages
CREATE TABLE "pages" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "slug" VARCHAR(191) NOT NULL, "title" VARCHAR(255) NOT NULL, "content" TEXT NULL, "is_published" INTEGER NOT NULL DEFAULT 1, "created_at" TEXT NULL, "updated_at" TEXT NULL, UNIQUE ("slug"));
