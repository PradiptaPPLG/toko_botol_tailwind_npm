<?php

class CreateStokKeluarTable extends Migration
{
    public function up(): void
    {
        $this->createTable('stok_keluar', function (Schema $s) {
            $s->id();
            $s->integer('produk_id');
            $s->integer('jumlah');
            $s->enum('kondisi', ['rusak', 'transfer']);
            $s->integerNullable('cabang_asal');
            $s->integerNullable('cabang_tujuan');
            $s->textNullable('keterangan');
            $s->stringNullable('batch_id', 50);
            $s->timestamp('created_at');
            $s->index('produk_id');
            $s->index('cabang_asal');
            $s->index('cabang_tujuan');
            $s->index('batch_id', 'idx_batch_id');
            $s->foreign('produk_id', 'produk');
            $s->foreign('cabang_asal', 'cabang');
            $s->foreign('cabang_tujuan', 'cabang');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('stok_keluar');
    }
}
