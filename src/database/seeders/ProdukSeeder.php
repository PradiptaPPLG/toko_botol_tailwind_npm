<?php

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncate('produk');

        $this->insertMany('produk', [
            [
                'kode_produk'  => 'AGK',
                'nama_produk'  => 'AG KAKAKTUA',
                'satuan'       => 'botol',
                'harga_beli'   => 45000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'ARCIL',
                'nama_produk'  => 'ARCIL',
                'satuan'       => 'botol',
                'harga_beli'   => 30000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'BB',
                'nama_produk'  => 'BINTANG',
                'satuan'       => 'botol',
                'harga_beli'   => 35000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'DB',
                'nama_produk'  => 'DRUM BESAR',
                'satuan'       => 'botol',
                'harga_beli'   => 146000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'DK',
                'nama_produk'  => 'DRUM KECIL',
                'satuan'       => 'botol',
                'harga_beli'   => 75000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'AOB',
                'nama_produk'  => 'AOB',
                'satuan'       => 'botol',
                'harga_beli'   => 60000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'AM',
                'nama_produk'  => 'AM',
                'satuan'       => 'botol',
                'harga_beli'   => 60000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'KMPT',
                'nama_produk'  => 'KAMPUT',
                'satuan'       => 'botol',
                'harga_beli'   => 55000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'KH',
                'nama_produk'  => 'KH',
                'satuan'       => 'botol',
                'harga_beli'   => 69000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'BH',
                'nama_produk'  => 'GUINNES',
                'satuan'       => 'botol',
                'harga_beli'   => 45000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'ATLAS',
                'nama_produk'  => 'ATLAS',
                'satuan'       => 'botol',
                'harga_beli'   => 62000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'IBC',
                'nama_produk'  => 'INTISARI BC',
                'satuan'       => 'botol',
                'harga_beli'   => 60000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'ICB',
                'nama_produk'  => 'ICB',
                'satuan'       => 'botol',
                'harga_beli'   => 105000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'INTI',
                'nama_produk'  => 'INTISARI',
                'satuan'       => 'botol',
                'harga_beli'   => 45000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'JKR',
                'nama_produk'  => 'JOKER',
                'satuan'       => 'botol',
                'harga_beli'   => 62000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'API',
                'nama_produk'  => 'API',
                'satuan'       => 'botol',
                'harga_beli'   => 69000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'AOM',
                'nama_produk'  => 'AO MILD',
                'satuan'       => 'botol',
                'harga_beli'   => 60000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'VIBE',
                'nama_produk'  => 'VIBE',
                'satuan'       => 'botol',
                'harga_beli'   => 250000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'CM',
                'nama_produk'  => 'C. MORGAN',
                'satuan'       => 'botol',
                'harga_beli'   => 280000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'AP',
                'nama_produk'  => 'AP',
                'satuan'       => 'botol',
                'harga_beli'   => 58000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'AGP',
                'nama_produk'  => 'AG PREMIUM',
                'satuan'       => 'botol',
                'harga_beli'   => 41000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'PROST',
                'nama_produk'  => 'PROST',
                'satuan'       => 'botol',
                'harga_beli'   => 25000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'KWBC',
                'nama_produk'  => 'KWBC',
                'satuan'       => 'botol',
                'harga_beli'   => 64000,
                'status'       => 'active',
            ],
            [
                'kode_produk'  => 'AG',
                'nama_produk'  => 'AG',
                'satuan'       => 'botol',
                'harga_beli'   => 35500,
                'status'       => 'active',
            ],
        ]);
    }
}
