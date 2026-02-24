<?php

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncate('admin');

        $this->insert('admin', [
            'username'     => 'admin',
            'password'     => '$2y$10$zb0s6bb7tnCkBP4L25V5d.6k6LiU.9.Ndf3G/WRZ3Eps8gZmgc.O6',
            'nama_lengkap' => 'Admin Utama',
        ]);
    }
}
