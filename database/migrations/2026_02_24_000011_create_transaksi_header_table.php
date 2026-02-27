<?php

class CreateTransaksiHeaderTable extends Migration
{
    public function up(): void
    {
        $this->createTable('transaksi_header', function (Schema $s) {
            $s->id();
            $s->string('no_invoice', 50);
            $s->integer('cabang_id');
            $s->integerNullable('session_kasir_id');
            $s->string('nama_kasir', 100);
            $s->enum('tipe', ['pembeli', 'penjual'])->default('pembeli');
            $s->integerNullable('total_items')->default(0);
            $s->integer('total_harga')->default(0);
            $s->timestamp('created_at');
            $s->unique('no_invoice');
            $s->index('cabang_id');
            $s->index('session_kasir_id');
            $s->index('created_at', 'idx_created_at');
            $s->index('tipe', 'idx_tipe');
            $s->foreign('cabang_id', 'cabang');
            $s->foreign('session_kasir_id', 'session_kasir', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('transaksi_header');
    }
}
