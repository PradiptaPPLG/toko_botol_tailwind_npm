<?php

class CreateCabangTable extends Migration
{
    public function up(): void
    {
        $this->createTable('cabang', function (Schema $s) {
            $s->id();
            $s->string('nama_cabang', 50);
            $s->textNullable('alamat');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('cabang');
    }
}
