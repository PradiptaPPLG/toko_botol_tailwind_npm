<?php

class StokCabangSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncate('stok_cabang');

        // produk_id => [cabang1_stok, cabang2_stok, cabang3_stok]
        $stocks = [
            1 => [30, 30, 30],
            2 => [30, 30, 30],
            3 => [15, 15, 15],
        ];

        foreach ($stocks as $produk_id => $stok_per_cabang) {
            foreach ($stok_per_cabang as $cabang_id => $stok) {
                $this->insert('stok_cabang', [
                    'produk_id' => $produk_id,
                    'cabang_id' => $cabang_id + 1,
                    'stok'      => $stok,
                ]);
            }
        }
    }
}
