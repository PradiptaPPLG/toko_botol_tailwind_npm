<?php

class CreateRiwayatLoginKasirTable extends Migration
{
    public function up(): void
    {
        $this->createTable('riwayat_login_kasir', function (Schema $s) {
            $s->id();
            $s->string('nama_kasir', 100);
            $s->integer('cabang_id');
            $s->timestamp('login_time');
            $s->index('cabang_id');
            $s->foreign('cabang_id', 'cabang');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('riwayat_login_kasir');
    }
}
