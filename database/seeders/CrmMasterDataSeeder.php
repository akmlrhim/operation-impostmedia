<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CrmMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LeadStageSeeder::class);
        $this->call(ServiceSeeder::class);
    }
}
