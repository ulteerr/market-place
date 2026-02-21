<?php

declare(strict_types=1);

namespace Modules\Organizations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationJoinRequest;
use Modules\Users\Models\User;

/**
 * @extends Factory<OrganizationJoinRequest>
 */
final class OrganizationJoinRequestFactory extends Factory
{
    protected $model = OrganizationJoinRequest::class;

    public function definition(): array
    {
        return [
            "organization_id" => Organization::factory(),
            "user_id" => User::factory(),
            "status" => $this->faker->randomElement(["pending", "approved", "rejected"]),
            "message" => $this->faker->optional()->sentence(),
            "review_note" => null,
            "reviewed_by_user_id" => null,
            "reviewed_at" => null,
        ];
    }

    public function pending(): self
    {
        return $this->state(
            fn(): array => [
                "status" => "pending",
                "review_note" => null,
                "reviewed_by_user_id" => null,
                "reviewed_at" => null,
            ],
        );
    }
}
