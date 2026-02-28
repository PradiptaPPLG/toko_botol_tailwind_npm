<?php

class AddBotolPerdusAndPengeluaranSoftdelete extends Migration
{
    public function up(): void
    {
        // Add botol_perdus column to produk table
        $this->execute("ALTER TABLE produk ADD COLUMN botol_perdus INT NOT NULL DEFAULT 12 AFTER satuan");

        // Add deleted_at column to pengeluaran table for soft delete
        $this->execute("ALTER TABLE pengeluaran ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE produk DROP COLUMN botol_perdus");
        $this->execute("ALTER TABLE pengeluaran DROP COLUMN deleted_at");
    }
}
