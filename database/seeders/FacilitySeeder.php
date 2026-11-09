<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Facility::factory()->createMany([
            ['name' => '中央図書館', 'address' => '本町1-1-1'],
            ['name' => '駅前分館', 'address' => '駅前2-3-4'],
            ['name' => '南分館', 'address' => '南町5-6-7'],
        ]);
    }
}
