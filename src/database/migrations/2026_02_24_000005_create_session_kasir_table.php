<?php

class CreateSessionKasirTable extends Migration
{
    public function up(): void
    {
        $this->createTable('session_kasir', function (Schema $s) {
            $s->id();
            $s->string('nama_kasir', 100);
            $s->integer('cabang_id');
            $s->timestamp('login_time');
            $s->datetimeNullable('logout_time');
            $s->enum('status', ['login', 'logout'])->default('login');
            $s->index('cabang_id');
            $s->foreign('cabang_id', 'cabang');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('session_kasir');
    }
}
