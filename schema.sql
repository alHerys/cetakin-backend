-- ============================================================
-- On-Demand Print Service App — PostgreSQL Database Schema
-- ============================================================

-- Enable UUID generation
CREATE EXTENSION IF NOT EXISTS "pgcrypto";


-- ============================================================
-- 1. USERS
-- ============================================================
CREATE TABLE users (
    id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name          VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    phone         VARCHAR(20),
    role          VARCHAR(20) NOT NULL CHECK (role IN ('user', 'partner', 'admin')),
    created_at    TIMESTAMP DEFAULT NOW(),
    updated_at    TIMESTAMP DEFAULT NOW()
);


-- ============================================================
-- 2. SHOPS
-- ============================================================
CREATE TABLE shops (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id             UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    shop_name           VARCHAR(255) NOT NULL,
    shop_address        TEXT NOT NULL,
    shop_phone          VARCHAR(20),
    shop_description    TEXT,
    shop_photo_url      TEXT,
    open_time           TIME NOT NULL,
    close_time          TIME NOT NULL,
    operating_days      TEXT[] NOT NULL DEFAULT '{}',
    latitude            DECIMAL(10, 7),
    longitude           DECIMAL(10, 7),
    status              VARCHAR(20) NOT NULL DEFAULT 'pending'
                            CHECK (status IN ('pending', 'approved', 'rejected')),
    rejection_reason    TEXT,
    average_rating      DECIMAL(3, 2) DEFAULT 0.00,
    total_reviews       INTEGER DEFAULT 0,
    created_at          TIMESTAMP DEFAULT NOW(),
    updated_at          TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_shops_status ON shops(status);
CREATE INDEX idx_shops_location ON shops(latitude, longitude);
CREATE INDEX idx_shops_user_id ON shops(user_id);


-- ============================================================
-- 3. SHOP SERVICES
-- Stores which print options each shop supports
-- ============================================================
CREATE TABLE shop_services (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id         UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE UNIQUE,
    paper_sizes     TEXT[] NOT NULL DEFAULT '{}',
    color_modes     TEXT[] NOT NULL DEFAULT '{}',
    sides           TEXT[] NOT NULL DEFAULT '{}',
    bindings        TEXT[] NOT NULL DEFAULT '{}',
    updated_at      TIMESTAMP DEFAULT NOW()
);


-- ============================================================
-- 4. SHOP PRICING
-- Stores per-unit print pricing per shop
-- ============================================================
CREATE TABLE shop_pricing (
    id                          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id                     UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE UNIQUE,
    black_and_white_per_page    INTEGER NOT NULL DEFAULT 0,
    full_color_per_page         INTEGER NOT NULL DEFAULT 0,
    double_side_surcharge       INTEGER NOT NULL DEFAULT 0,
    binding_prices              JSONB NOT NULL DEFAULT '{}',
    updated_at                  TIMESTAMP DEFAULT NOW()
);


-- ============================================================
-- 5. ATK PRODUCTS
-- Each shop manages their own ATK catalog
-- ============================================================
CREATE TABLE atk_products (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shop_id         UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE,
    name            VARCHAR(255) NOT NULL,
    description     TEXT,
    price           INTEGER NOT NULL,
    stock           INTEGER NOT NULL DEFAULT 0,
    photo_url       TEXT,
    is_available    BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_atk_products_shop_id ON atk_products(shop_id);


-- ============================================================
-- 6. PRINT ORDERS
-- ============================================================
CREATE TABLE print_orders (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    shop_id         UUID NOT NULL REFERENCES shops(id) ON DELETE RESTRICT,
    file_url        TEXT NOT NULL,
    paper_size      VARCHAR(10) NOT NULL CHECK (paper_size IN ('A4', 'A3', 'F4')),
    color_mode      VARCHAR(20) NOT NULL CHECK (color_mode IN ('black_and_white', 'full_color')),
    sides           VARCHAR(10) NOT NULL CHECK (sides IN ('single', 'double')),
    binding         VARCHAR(20) NOT NULL CHECK (binding IN ('none', 'staple', 'spiral')),
    copies          INTEGER NOT NULL DEFAULT 1,
    total_pages     INTEGER NOT NULL DEFAULT 0,
    final_price     INTEGER NOT NULL DEFAULT 0,
    notes           TEXT,
    status          VARCHAR(30) NOT NULL DEFAULT 'pending'
                        CHECK (status IN (
                            'pending',
                            'confirmed',
                            'processing',
                            'ready_for_pickup',
                            'completed'
                        )),
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_print_orders_user_id ON print_orders(user_id);
CREATE INDEX idx_print_orders_shop_id ON print_orders(shop_id);
CREATE INDEX idx_print_orders_status ON print_orders(status);


-- ============================================================
-- 7. PRINT ORDER STATUS HISTORY
-- Tracks every status transition for a print order
-- ============================================================
CREATE TABLE print_order_status_history (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id    UUID NOT NULL REFERENCES print_orders(id) ON DELETE CASCADE,
    status      VARCHAR(30) NOT NULL,
    created_at  TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_print_status_history_order_id ON print_order_status_history(order_id);


-- ============================================================
-- 8. ATK ORDERS
-- ============================================================
CREATE TABLE atk_orders (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    shop_id         UUID NOT NULL REFERENCES shops(id) ON DELETE RESTRICT,
    final_price     INTEGER NOT NULL DEFAULT 0,
    notes           TEXT,
    status          VARCHAR(30) NOT NULL DEFAULT 'pending'
                        CHECK (status IN (
                            'pending',
                            'confirmed',
                            'processing',
                            'ready_for_pickup',
                            'completed'
                        )),
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_atk_orders_user_id ON atk_orders(user_id);
CREATE INDEX idx_atk_orders_shop_id ON atk_orders(shop_id);
CREATE INDEX idx_atk_orders_status ON atk_orders(status);


-- ============================================================
-- 9. ATK ORDER ITEMS
-- Line items for each ATK order
-- ============================================================
CREATE TABLE atk_order_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    atk_order_id    UUID NOT NULL REFERENCES atk_orders(id) ON DELETE CASCADE,
    atk_product_id  UUID NOT NULL REFERENCES atk_products(id) ON DELETE RESTRICT,
    name            VARCHAR(255) NOT NULL,
    quantity        INTEGER NOT NULL DEFAULT 1,
    unit_price      INTEGER NOT NULL,
    subtotal        INTEGER NOT NULL
);

CREATE INDEX idx_atk_order_items_order_id ON atk_order_items(atk_order_id);


-- ============================================================
-- 10. ATK ORDER STATUS HISTORY
-- Tracks every status transition for an ATK order
-- ============================================================
CREATE TABLE atk_order_status_history (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id    UUID NOT NULL REFERENCES atk_orders(id) ON DELETE CASCADE,
    status      VARCHAR(30) NOT NULL,
    created_at  TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_atk_status_history_order_id ON atk_order_status_history(order_id);


-- ============================================================
-- 11. REVIEWS
-- Unified review table for both print and ATK orders
-- ============================================================
CREATE TABLE reviews (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id             UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    shop_id             UUID NOT NULL REFERENCES shops(id) ON DELETE CASCADE,
    order_type          VARCHAR(10) NOT NULL CHECK (order_type IN ('print', 'atk')),
    print_order_id      UUID REFERENCES print_orders(id) ON DELETE SET NULL,
    atk_order_id        UUID REFERENCES atk_orders(id) ON DELETE SET NULL,
    rating              SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment             TEXT,
    created_at          TIMESTAMP DEFAULT NOW(),

    -- Ensure only one review per order
    CONSTRAINT unique_print_order_review UNIQUE (print_order_id),
    CONSTRAINT unique_atk_order_review   UNIQUE (atk_order_id),

    -- Ensure the correct order id is populated based on order_type
    CONSTRAINT chk_order_reference CHECK (
        (order_type = 'print' AND print_order_id IS NOT NULL AND atk_order_id IS NULL) OR
        (order_type = 'atk'   AND atk_order_id IS NOT NULL  AND print_order_id IS NULL)
    )
);

CREATE INDEX idx_reviews_shop_id ON reviews(shop_id);
CREATE INDEX idx_reviews_user_id ON reviews(user_id);


-- ============================================================
-- 12. TRIGGER — Auto-update average_rating on shops
-- Recalculates shop rating whenever a review is inserted
-- ============================================================
CREATE OR REPLACE FUNCTION update_shop_rating()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE shops
    SET
        average_rating = (
            SELECT ROUND(AVG(rating)::NUMERIC, 2)
            FROM reviews
            WHERE shop_id = NEW.shop_id
        ),
        total_reviews = (
            SELECT COUNT(*)
            FROM reviews
            WHERE shop_id = NEW.shop_id
        ),
        updated_at = NOW()
    WHERE id = NEW.shop_id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_update_shop_rating
AFTER INSERT ON reviews
FOR EACH ROW
EXECUTE FUNCTION update_shop_rating();


-- ============================================================
-- 13. TRIGGER — Auto-insert initial status history on order create
-- ============================================================
CREATE OR REPLACE FUNCTION log_initial_print_order_status()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO print_order_status_history (order_id, status, created_at)
    VALUES (NEW.id, NEW.status, NOW());
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_log_initial_print_status
AFTER INSERT ON print_orders
FOR EACH ROW
EXECUTE FUNCTION log_initial_print_order_status();

-- ---

CREATE OR REPLACE FUNCTION log_print_order_status_change()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status <> OLD.status THEN
        INSERT INTO print_order_status_history (order_id, status, created_at)
        VALUES (NEW.id, NEW.status, NOW());
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_log_print_status_change
AFTER UPDATE ON print_orders
FOR EACH ROW
EXECUTE FUNCTION log_print_order_status_change();

-- ---

CREATE OR REPLACE FUNCTION log_initial_atk_order_status()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO atk_order_status_history (order_id, status, created_at)
    VALUES (NEW.id, NEW.status, NOW());
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_log_initial_atk_status
AFTER INSERT ON atk_orders
FOR EACH ROW
EXECUTE FUNCTION log_initial_atk_order_status();

-- ---

CREATE OR REPLACE FUNCTION log_atk_order_status_change()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status <> OLD.status THEN
        INSERT INTO atk_order_status_history (order_id, status, created_at)
        VALUES (NEW.id, NEW.status, NOW());
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_log_atk_status_change
AFTER UPDATE ON atk_orders
FOR EACH ROW
EXECUTE FUNCTION log_atk_order_status_change();


-- ============================================================
-- SEED — Default admin account
-- Password is hashed using pgcrypto bcrypt (blowfish, cost factor 12)
-- Change the password string below before running in production
-- ============================================================
INSERT INTO users (name, email, password, role)
VALUES (
    'Super Admin',
    'admin@printapp.com',
    crypt('admin123', gen_salt('bf', 12)),
    'admin'
);
