<?php

class CreateStokCabangTable extends Migration
{
    public function up(): void
    {
        $this->createTable('stok_cabang', function (Schema $s) {
            $s->id();
            $s->integer('produk_id');
            $s->integer('cabang_id');
            $s->integer('stok')->default(0);
            $s->unique('produk_cabang', ['produk_id', 'cabang_id']);
            $s->index('cabang_id');
            $s->foreign('produk_id', 'produk', 'id', 'CASCADE');
            $s->foreign('cabang_id', 'cabang', 'id', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('stok_cabang');
    }
}
