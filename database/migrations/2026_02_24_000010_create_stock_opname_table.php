<?php

class CreateStockOpnameTable extends Migration
{
    public function up(): void
    {
        $this->createTable('stock_opname', function (Schema $s) {
            $s->id();
            $s->integer('produk_id');
            $s->integer('stok_sistem');
            $s->integer('stok_fisik');
            $s->integer('selisih');
            $s->string('status', 20)->default('HILANG');
            $s->integer('is_cancelled')->tinyint()->default(0);
            $s->string('petugas', 100);
            $s->date('tanggal');
            $s->timestamp('created_at');
            $s->index('produk_id');
            $s->foreign('produk_id', 'produk');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('stock_opname');
    }
}
