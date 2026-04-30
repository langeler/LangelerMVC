-- LangelerMVC release schema reference
-- Generated from framework and first-party module migrations.
-- SQLite-compatible reference SQL; migrations remain the authoritative runtime source.
-- Do not store live credentials, secrets, or deployment-local data in Data/*.sql.

-- carts
CREATE TABLE "carts" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "user_id" BIGINT NULL, "session_key" VARCHAR(255) NULL, "status" VARCHAR(32) NOT NULL DEFAULT 'active', "currency" VARCHAR(12) NOT NULL DEFAULT 'SEK', "created_at" TEXT NULL, "updated_at" TEXT NULL, "discount_code" VARCHAR(64), "discount_label" VARCHAR(191), "discount_snapshot" JSON, UNIQUE ("session_key"), FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE);
CREATE INDEX "carts_user_id_status_idx" ON "carts" ("user_id", "status");

-- cart_items
CREATE TABLE "cart_items" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "cart_id" BIGINT NOT NULL, "product_id" BIGINT NULL, "product_name" VARCHAR(191) NOT NULL, "unit_price_minor" INT NOT NULL DEFAULT 0, "quantity" INT NOT NULL DEFAULT 1, "line_total_minor" INT NOT NULL DEFAULT 0, "metadata" TEXT NULL, "created_at" TEXT NULL, "updated_at" TEXT NULL, FOREIGN KEY ("cart_id") REFERENCES "carts"("id") ON DELETE CASCADE ON UPDATE CASCADE);
CREATE INDEX "cart_items_cart_id_product_id_idx" ON "cart_items" ("cart_id", "product_id");

-- promotions
CREATE TABLE "promotions" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "code" VARCHAR(64) NOT NULL, "label" VARCHAR(191) NOT NULL, "description" TEXT NULL, "type" VARCHAR(40) NOT NULL DEFAULT 'fixed_amount', "applies_to" VARCHAR(40) NOT NULL DEFAULT 'cart_subtotal', "active" INTEGER NOT NULL DEFAULT 1, "rate_bps" INT NOT NULL DEFAULT 0, "amount_minor" INT NOT NULL DEFAULT 0, "shipping_rate_minor" INT NOT NULL DEFAULT 0, "min_subtotal_minor" INT NOT NULL DEFAULT 0, "max_subtotal_minor" INT NOT NULL DEFAULT 0, "max_discount_minor" INT NOT NULL DEFAULT 0, "min_items" INT NOT NULL DEFAULT 0, "max_items" INT NOT NULL DEFAULT 0, "usage_limit" INT NOT NULL DEFAULT 0, "usage_count" INT NOT NULL DEFAULT 0, "starts_at" TEXT NULL, "ends_at" TEXT NULL, "criteria" TEXT NULL, "source" VARCHAR(32) NOT NULL DEFAULT 'database', "created_at" TEXT NULL, "updated_at" TEXT NULL, UNIQUE ("code"));
CREATE INDEX "promotions_active_code_idx" ON "promotions" ("active", "code");
CREATE INDEX "promotions_type_active_idx" ON "promotions" ("type", "active");

-- promotion_usages
CREATE TABLE "promotion_usages" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "promotion_id" BIGINT NULL, "promotion_code" VARCHAR(64) NOT NULL, "order_id" BIGINT NULL, "cart_id" BIGINT NULL, "user_id" BIGINT NULL, "currency" VARCHAR(12) NOT NULL DEFAULT 'SEK', "discount_minor" INT NOT NULL DEFAULT 0, "item_discount_minor" INT NOT NULL DEFAULT 0, "shipping_discount_minor" INT NOT NULL DEFAULT 0, "source" VARCHAR(32) NOT NULL DEFAULT 'database', "context" TEXT NULL, "created_at" TEXT NULL, UNIQUE ("order_id", "promotion_code"));
CREATE INDEX "promotion_usages_promotion_code_created_at_idx" ON "promotion_usages" ("promotion_code", "created_at");
CREATE INDEX "promotion_usages_promotion_id_created_at_idx" ON "promotion_usages" ("promotion_id", "created_at");
CREATE INDEX "promotion_usages_user_id_promotion_code_idx" ON "promotion_usages" ("user_id", "promotion_code");
