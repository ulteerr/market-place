<?php

declare(strict_types=1);

namespace Modules\Organizations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organizations\Models\Organization;
use Modules\Users\Models\User;

/**
 * @extends Factory<Organization>
 */
final class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            "name" => "Организация " . $this->faker->unique()->company(),
            "description" => $this->faker->optional()->sentence(),
            "phone" => $this->faker->optional()->phoneNumber(),
            "email" => $this->faker->optional()->safeEmail(),
            "status" => $this->faker->randomElement(["draft", "active", "suspended", "archived"]),
            "source_type" => $this->faker->randomElement([
                "manual",
                "import",
                "parsed",
                "self_registered",
            ]),
            "ownership_status" => $this->faker->randomElement([
                "unclaimed",
                "pending_claim",
                "claimed",
            ]),
            "user_id" => User::factory(),
            "owner_user_id" => null,
            "created_by_user_id" => null,
            "claimed_at" => null,
        ];
    }
}
