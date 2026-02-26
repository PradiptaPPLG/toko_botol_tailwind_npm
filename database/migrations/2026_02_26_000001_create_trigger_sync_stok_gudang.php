<?php

class CreateTriggerSyncStokGudang extends Migration
{
    public function up(): void
    {
        // Create trigger to sync stok_gudang after INSERT on stok_cabang
        $this->execute("
            CREATE TRIGGER IF NOT EXISTS sync_stok_after_insert
            AFTER INSERT ON stok_cabang
            FOR EACH ROW
            BEGIN
                UPDATE produk
                SET stok_gudang = (
                    SELECT COALESCE(SUM(stok), 0)
                    FROM stok_cabang
                    WHERE produk_id = NEW.produk_id
                )
                WHERE id = NEW.produk_id;
            END
        ");

        // Create trigger to sync stok_gudang after UPDATE on stok_cabang
        $this->execute("
            CREATE TRIGGER IF NOT EXISTS sync_stok_after_update
            AFTER UPDATE ON stok_cabang
            FOR EACH ROW
            BEGIN
                UPDATE produk
                SET stok_gudang = (
                    SELECT COALESCE(SUM(stok), 0)
                    FROM stok_cabang
                    WHERE produk_id = NEW.produk_id
                )
                WHERE id = NEW.produk_id;
            END
        ");

        // Create trigger to sync stok_gudang after DELETE on stok_cabang
        $this->execute("
            CREATE TRIGGER IF NOT EXISTS sync_stok_after_delete
            AFTER DELETE ON stok_cabang
            FOR EACH ROW
            BEGIN
                UPDATE produk
                SET stok_gudang = (
                    SELECT COALESCE(SUM(stok), 0)
                    FROM stok_cabang
                    WHERE produk_id = OLD.produk_id
                )
                WHERE id = OLD.produk_id;
            END
        ");

        // Initial sync: set stok_gudang = sum of all cabang stok
        $this->execute("
            UPDATE produk p
            LEFT JOIN (
                SELECT produk_id, COALESCE(SUM(stok), 0) AS total_stok
                FROM stok_cabang
                GROUP BY produk_id
            ) sc ON p.id = sc.produk_id
            SET p.stok_gudang = COALESCE(sc.total_stok, 0)
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TRIGGER IF EXISTS sync_stok_after_insert");
        $this->execute("DROP TRIGGER IF EXISTS sync_stok_after_update");
        $this->execute("DROP TRIGGER IF EXISTS sync_stok_after_delete");
    }
}
