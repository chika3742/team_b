<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            FacilitySeeder::class,
            BookSeeder::class,
        ]);

        User::factory()->create([
            'user_card_id' => '1000001',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'home_facility_id' => Facility::query()->value('id'),
        ]);

        User::factory()->staff()->create([
            'user_card_id' => '9000001',
            'name' => 'Test Staff',
            'email' => 'staff@example.com',
        ]);
    }
}
