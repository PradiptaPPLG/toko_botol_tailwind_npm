<?php

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncate('admin');

        $this->insert('admin', [
            'username'     => 'admin',
            'password'     => password_hash('@admin123', PASSWORD_BCRYPT, ['cost' => 12]),
            'nama_lengkap' => 'Admin',
        ]);
    }
}
