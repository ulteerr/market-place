<?php

declare(strict_types=1);

namespace Modules\Children\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Children\Models\Child;
use Modules\Users\Models\User;

/**
 * @extends Factory<Child>
 */
final class ChildFactory extends Factory
{
    protected $model = Child::class;

    public function definition(): array
    {
        return [
            "user_id" => User::factory(),
            "first_name" => $this->faker->firstName(),
            "last_name" => $this->faker->lastName(),
            "middle_name" => $this->faker->optional()->firstName(),
            "gender" => $this->faker->randomElement(["male", "female"]),
            "birth_date" => $this->faker->date(),
        ];
    }
}
