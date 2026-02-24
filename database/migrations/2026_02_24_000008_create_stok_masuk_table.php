<?php

class CreateStokMasukTable extends Migration
{
    public function up(): void
    {
        $this->createTable('stok_masuk', function (Schema $s) {
            $s->id();
            $s->integer('produk_id');
            $s->integer('jumlah');
            $s->integer('harga_beli_satuan')->default(0);
            $s->integer('total_belanja')->default(0);
            $s->stringNullable('keterangan', 255);
            $s->stringNullable('batch_id', 50);
            $s->timestamp('created_at');
            $s->index('produk_id');
            $s->index('batch_id', 'idx_batch_id');
            $s->index('total_belanja', 'idx_total_belanja');
            $s->foreign('produk_id', 'produk');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('stok_masuk');
    }
}
