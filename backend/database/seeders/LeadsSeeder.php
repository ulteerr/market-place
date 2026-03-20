<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Activities\Database\Seeders\LeadsScenarioSeeder;

final class LeadsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([LeadsScenarioSeeder::class]);
    }
}
