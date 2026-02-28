<?php

class CreateTransaksiDetailTable extends Migration
{
    public function up(): void
    {
        $this->createTable('transaksi_detail', function (Schema $s) {
            $s->id();
            $s->integer('transaksi_header_id');
            $s->string('no_invoice', 50);
            $s->integer('produk_id');
            $s->string('nama_produk', 100);
            $s->integer('jumlah');
            $s->string('satuan', 20)->default('botol');
            $s->integer('harga_satuan');
            $s->integer('subtotal');
            $s->timestamp('created_at');
            $s->index('transaksi_header_id');
            $s->index('no_invoice');
            $s->index('produk_id');
            $s->foreign('transaksi_header_id', 'transaksi_header', 'id', 'CASCADE');
            $s->foreign('produk_id', 'produk');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('transaksi_detail');
    }
}
