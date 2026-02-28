<?php

class AddSudahDisetorColumnToTransaksiHeaderTable extends Migration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE transaksi_header ADD COLUMN sudah_disetor BOOLEAN DEFAULT 0 AFTER total_harga");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE transaksi_header DROP COLUMN sudah_disetor");
    }
}
