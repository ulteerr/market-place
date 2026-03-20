<?php

declare(strict_types=1);

namespace Modules\Activities\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Activities\Models\Activity;
use Modules\Activities\Models\ActivitySchedule;

/**
 * @extends Factory<ActivitySchedule>
 */
final class ActivityScheduleFactory extends Factory
{
    protected $model = ActivitySchedule::class;

    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(9, 18);
        $endHour = min($startHour + 1, 23);

        return [
            "activity_id" => Activity::factory(),
            "day_of_week" => $this->faker->numberBetween(1, 7),
            "start_time" => sprintf("%02d:00:00", $startHour),
            "end_time" => sprintf("%02d:30:00", $endHour),
        ];
    }

    public function forActivity(Activity|string $activity): self
    {
        return $this->state(
            fn() => [
                "activity_id" =>
                    $activity instanceof Activity ? (string) $activity->getKey() : $activity,
            ],
        );
    }
}
