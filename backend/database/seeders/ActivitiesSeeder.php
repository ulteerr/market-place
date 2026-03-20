<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Activities\Database\Seeders\ActivitiesScenarioSeeder;

class ActivitiesSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([ActivitiesScenarioSeeder::class]);
    }
}
