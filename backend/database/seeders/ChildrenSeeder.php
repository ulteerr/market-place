<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Children\Database\Seeders\ChildrenSeeder as ModuleChildrenSeeder;

class ChildrenSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([ModuleChildrenSeeder::class]);
    }
}
