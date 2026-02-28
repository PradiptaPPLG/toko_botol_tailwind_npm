<?php

require_once __DIR__ . '/AdminSeeder.php';
require_once __DIR__ . '/CabangSeeder.php';
require_once __DIR__ . '/ProdukSeeder.php';
require_once __DIR__ . '/StokCabangSeeder.php';

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Order matters: parent tables must be seeded before child tables
        $this->call('AdminSeeder');
        $this->call('CabangSeeder');
        //$this->call('ProdukSeeder');
        //$this->call('StokCabangSeeder');
    }
}
