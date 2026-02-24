<?php

class CabangSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncate('cabang');

        $this->insertMany('cabang', [
            ['nama_cabang' => 'Barat',  'alamat' => 'Jl. Raya Barat No.1'],
            ['nama_cabang' => 'Pusat',  'alamat' => 'Jl. Raya Pusat No.2'],
            ['nama_cabang' => 'Timur',  'alamat' => 'Jl. Raya Timur No.3'],
        ]);
    }
}
