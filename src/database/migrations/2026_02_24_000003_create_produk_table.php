<?php

class CreateProdukTable extends Migration
{
    public function up(): void
    {
        $this->createTable('produk', function (Schema $s) {
            $s->id();
            $s->string('kode_produk', 50);
            $s->string('nama_produk', 100);
            $s->string('satuan', 20)->default('botol');
            $s->integer('harga_beli')->default(0);
            $s->integer('stok_gudang')->default(0);
            $s->enum('status', ['active', 'deleted'])->default('active');
            $s->datetimeNullable('deleted_at');
            $s->timestamps();
            $s->unique('kode_produk');
            $s->index('status');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('produk');
    }
}
