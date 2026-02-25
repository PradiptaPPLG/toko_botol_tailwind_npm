<?php

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncate('admin');

        $this->insert('admin', [
            'username'     => 'pdk',
            'password'     => password_hash('op.delia', PASSWORD_BCRYPT, ['cost' => 12]),
            'nama_lengkap' => 'PDK',
        ]);
    }
}
