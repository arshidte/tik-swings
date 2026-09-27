<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call('SettingsSeeder');
        $this->call('CatalogSeeder');
        $this->call('ContentSeeder');
    }
}
