<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivitiesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('activities')->insert([
            [
                'organization_id' => 1,
                'location_id' => 1,
                'name' => 'Футбольная секция',
                'description' => 'Детская футбольная школа',
                'min_age' => 6,
                'max_age' => 12,
                'capacity' => 20,
                'price_from' => 1500,
                'price_to' => 3000,
                'currency' => 'RUB',
                'status' => 'active'
            ],
            [
                'organization_id' => 2,
                'location_id' => 2,
                'name' => 'Музыкальная студия',
                'description' => 'Занятия музыкой для детей',
                'min_age' => 5,
                'max_age' => 10,
                'capacity' => 15,
                'price_from' => 2000,
                'price_to' => 4000,
                'currency' => 'RUB',
                'status' => 'active'
            ],
        ]);

        DB::table('activity_schedules')->insert([
            ['activity_id' => 1, 'day_of_week' => 1, 'time_start' => '17:00', 'time_end' => '18:30'],
            ['activity_id' => 1, 'day_of_week' => 3, 'time_start' => '17:00', 'time_end' => '18:30'],
            ['activity_id' => 2, 'day_of_week' => 2, 'time_start' => '16:00', 'time_end' => '17:30'],
            ['activity_id' => 2, 'day_of_week' => 4, 'time_start' => '16:00', 'time_end' => '17:30'],
        ]);
    }
}
