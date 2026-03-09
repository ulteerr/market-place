<?php

declare(strict_types=1);

namespace Modules\Organizations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Children\Models\Child;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationClient;
use Modules\Organizations\Models\OrganizationJoinRequest;
use Modules\Users\Models\User;

/**
 * @extends Factory<OrganizationClient>
 */
final class OrganizationClientFactory extends Factory
{
    protected $model = OrganizationClient::class;

    public function definition(): array
    {
        return [
            "organization_id" => Organization::factory(),
            "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
            "subject_id" => User::factory(),
            "status" => $this->faker->randomElement(["active", "left", "blocked"]),
            "added_by_user_id" => User::factory(),
            "joined_at" => $this->faker->optional()->dateTimeBetween("-2 years", "now"),
        ];
    }

    public function forUser(?User $user = null): self
    {
        return $this->state(function () use ($user): array {
            return [
                "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
                "subject_id" => $user?->id ?? User::factory(),
            ];
        });
    }

    public function forChild(?Child $child = null): self
    {
        return $this->state(function () use ($child): array {
            return [
                "subject_type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
                "subject_id" => $child?->id ?? Child::factory(),
            ];
        });
    }
}
