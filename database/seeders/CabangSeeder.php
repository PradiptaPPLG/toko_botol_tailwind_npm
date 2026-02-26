<?php

class CabangSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncate('cabang');

        // Pusat must be first (id=1) - this is the central warehouse
        $this->insertMany('cabang', [
            ['nama_cabang' => 'Pusat',  'alamat' => 'Gudang Pusat - Jl. Industri No. 1'],
            ['nama_cabang' => 'Barat',  'alamat' => 'Jl. Raya Barat No.1'],
            ['nama_cabang' => 'Timur',  'alamat' => 'Jl. Raya Timur No.3'],
        ]);
    }
}
