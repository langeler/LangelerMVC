-- LangelerMVC release schema reference
-- Generated from framework and first-party module migrations.
-- SQLite-compatible reference SQL; migrations remain the authoritative runtime source.
-- Do not store live credentials, secrets, or deployment-local data in Data/*.sql.

-- categories
CREATE TABLE "categories" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" VARCHAR(191) NOT NULL, "slug" VARCHAR(191) NOT NULL, "description" TEXT NULL, "is_published" INTEGER NOT NULL DEFAULT 1, "created_at" TEXT NULL, "updated_at" TEXT NULL, UNIQUE ("slug"));

-- products
CREATE TABLE "products" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "category_id" BIGINT NOT NULL, "name" VARCHAR(191) NOT NULL, "slug" VARCHAR(191) NOT NULL, "description" TEXT NULL, "price_minor" INT NOT NULL DEFAULT 0, "currency" VARCHAR(12) NOT NULL DEFAULT 'SEK', "visibility" VARCHAR(32) NOT NULL DEFAULT 'published', "media" TEXT NULL, "stock" INT NOT NULL DEFAULT 0, "created_at" TEXT NULL, "updated_at" TEXT NULL, "fulfillment_type" VARCHAR(40) NOT NULL DEFAULT 'physical_shipping', "fulfillment_policy" JSON, "available_at" TIMESTAMP, UNIQUE ("slug"), FOREIGN KEY ("category_id") REFERENCES "categories"("id") ON DELETE CASCADE ON UPDATE CASCADE);
CREATE INDEX "products_category_id_visibility_idx" ON "products" ("category_id", "visibility");
