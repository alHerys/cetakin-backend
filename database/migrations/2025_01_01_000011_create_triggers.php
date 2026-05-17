<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Auto-update shop average_rating and total_reviews on review insert
        DB::statement("
            CREATE OR REPLACE FUNCTION update_shop_rating()
            RETURNS TRIGGER AS \$\$
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
            \$\$ LANGUAGE plpgsql
        ");

        DB::statement("
            CREATE TRIGGER trg_update_shop_rating
            AFTER INSERT ON reviews
            FOR EACH ROW
            EXECUTE FUNCTION update_shop_rating()
        ");

        // Auto-log initial status when a print order is created
        DB::statement("
            CREATE OR REPLACE FUNCTION log_initial_print_order_status()
            RETURNS TRIGGER AS \$\$
            BEGIN
                INSERT INTO print_order_status_history (order_id, status, created_at)
                VALUES (NEW.id, NEW.status, NOW());
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql
        ");

        DB::statement("
            CREATE TRIGGER trg_log_initial_print_status
            AFTER INSERT ON print_orders
            FOR EACH ROW
            EXECUTE FUNCTION log_initial_print_order_status()
        ");

        // Auto-log status changes on print order updates
        DB::statement("
            CREATE OR REPLACE FUNCTION log_print_order_status_change()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF NEW.status <> OLD.status THEN
                    INSERT INTO print_order_status_history (order_id, status, created_at)
                    VALUES (NEW.id, NEW.status, NOW());
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql
        ");

        DB::statement("
            CREATE TRIGGER trg_log_print_status_change
            AFTER UPDATE ON print_orders
            FOR EACH ROW
            EXECUTE FUNCTION log_print_order_status_change()
        ");

        // Auto-log initial status when an ATK order is created
        DB::statement("
            CREATE OR REPLACE FUNCTION log_initial_atk_order_status()
            RETURNS TRIGGER AS \$\$
            BEGIN
                INSERT INTO atk_order_status_history (order_id, status, created_at)
                VALUES (NEW.id, NEW.status, NOW());
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql
        ");

        DB::statement("
            CREATE TRIGGER trg_log_initial_atk_status
            AFTER INSERT ON atk_orders
            FOR EACH ROW
            EXECUTE FUNCTION log_initial_atk_order_status()
        ");

        // Auto-log status changes on ATK order updates
        DB::statement("
            CREATE OR REPLACE FUNCTION log_atk_order_status_change()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF NEW.status <> OLD.status THEN
                    INSERT INTO atk_order_status_history (order_id, status, created_at)
                    VALUES (NEW.id, NEW.status, NOW());
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql
        ");

        DB::statement("
            CREATE TRIGGER trg_log_atk_status_change
            AFTER UPDATE ON atk_orders
            FOR EACH ROW
            EXECUTE FUNCTION log_atk_order_status_change()
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_update_shop_rating ON reviews');
        DB::statement('DROP TRIGGER IF EXISTS trg_log_initial_print_status ON print_orders');
        DB::statement('DROP TRIGGER IF EXISTS trg_log_print_status_change ON print_orders');
        DB::statement('DROP TRIGGER IF EXISTS trg_log_initial_atk_status ON atk_orders');
        DB::statement('DROP TRIGGER IF EXISTS trg_log_atk_status_change ON atk_orders');

        DB::statement('DROP FUNCTION IF EXISTS update_shop_rating');
        DB::statement('DROP FUNCTION IF EXISTS log_initial_print_order_status');
        DB::statement('DROP FUNCTION IF EXISTS log_print_order_status_change');
        DB::statement('DROP FUNCTION IF EXISTS log_initial_atk_order_status');
        DB::statement('DROP FUNCTION IF EXISTS log_atk_order_status_change');
    }
};
