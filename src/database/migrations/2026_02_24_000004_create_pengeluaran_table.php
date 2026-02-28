<?php

class CreatePengeluaranTable extends Migration
{
    public function up(): void
    {
        $this->createTable('pengeluaran', function (Schema $s) {
            $s->id();
            $s->integer('nominal');
            $s->string('keterangan', 255);
            $s->timestamp('created_at');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('pengeluaran');
    }
}
