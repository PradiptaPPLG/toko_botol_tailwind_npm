<?php

class CreateSetoranTable extends Migration
{
    public function up(): void
    {
        $this->createTable('setoran', function (Schema $s) {
            $s->id();
            $s->integer('cabang_id');
            $s->integer('total_setor')->default(0);
            $s->date('tanggal_dari');
            $s->date('tanggal_sampai');
            $s->textNullable('keterangan');
            $s->timestamp('created_at');
            $s->index('cabang_id');
            $s->index('created_at', 'idx_created_at');
            $s->foreign('cabang_id', 'cabang');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('setoran');
    }
}
