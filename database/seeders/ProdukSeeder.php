<?php

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncate('produk');

        $this->insertMany('produk', [
            [
                'kode_produk'  => 'BTL001',
                'nama_produk'  => 'Botol Kaca 330ml',
                'satuan'       => 'botol',
                'harga_beli'   => 1500,
                'harga_jual'   => 3000,
                'harga_dus'    => 72000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'BTL002',
                'nama_produk'  => 'Botol Plastik 500ml',
                'satuan'       => 'botol',
                'harga_beli'   => 800,
                'harga_jual'   => 2000,
                'harga_dus'    => 48000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'BTL003',
                'nama_produk'  => 'Botol Besar 1L',
                'satuan'       => 'botol',
                'harga_beli'   => 2500,
                'harga_jual'   => 5000,
                'harga_dus'    => 120000,
                'status'       => 'active',
            ],
        ]);
    }
}
