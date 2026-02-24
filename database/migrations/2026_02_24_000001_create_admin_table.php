<?php

class CreateAdminTable extends Migration
{
    public function up(): void
    {
        $this->createTable('admin', function (Schema $s) {
            $s->id();
            $s->string('username', 50);
            $s->string('password', 255);
            $s->string('nama_lengkap', 100);
            $s->unique('username');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('admin');
    }
}
