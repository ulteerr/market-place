<?php

declare(strict_types=1);

namespace Modules\Activities\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Activities\Database\Factories\LeadFactory;
use Modules\Activities\Models\Activity;
use Modules\Children\Models\Child;
use Modules\Users\Models\User;

final class LeadsScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $activity = Activity::query()->first();
        if (!$activity) {
            return;
        }

        $selfUser = User::factory()->create();
        LeadFactory::new()
            ->forActivity($activity)
            ->forUser($selfUser)
            ->selfRequest()
            ->viaPhone()
            ->create();

        $childUser = User::factory()->create();
        $child = Child::factory()->create([
            "user_id" => $childUser->id,
        ]);

        LeadFactory::new()
            ->forActivity($activity)
            ->forUser($childUser)
            ->childRequest($child)
            ->viaMessenger("telegram")
            ->inProgress()
            ->create([
                "message" => "Нужна консультация по расписанию и пробному занятию.",
            ]);
    }
}
