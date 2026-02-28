<?php

class CabangSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncate('cabang');

        // Pusat must be first (id=1) - this is the central warehouse
        $this->insertMany('cabang', [
            ['nama_cabang' => 'KONTER',  'alamat' => 'Jl. Mangin, Terminal Indihiang'],
            ['nama_cabang' => 'CIKURUBUK',  'alamat' => 'Jl. Pasar Cikurubuk'],
            ['nama_cabang' => 'SL.TOBING',  'alamat' => 'Jl. SL.Tobing'],
            ['nama_cabang' => 'GUDANG',  'alamat' => ''],
        ]);
    }
}
