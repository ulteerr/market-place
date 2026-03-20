<?php

declare(strict_types=1);

namespace Modules\Activities\Database\Factories;

use App\Shared\Support\Transliteration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Activities\Models\Activity;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;

/**
 * @extends Factory<Activity>
 */
final class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        $name = mb_convert_case($this->faker->unique()->words(3, true), MB_CASE_TITLE, "UTF-8");

        return [
            "organization_id" => Organization::factory(),
            "location_id" => function (array $attributes): string {
                return (string) OrganizationLocation::factory()
                    ->create(["organization_id" => $attributes["organization_id"]])
                    ->getKey();
            },
            "name" => $name,
            "slug" => Transliteration::slug($name),
            "short_description" => $this->faker->sentence(),
            "description" => $this->faker->optional()->paragraph(),
            "min_age" => $this->faker->optional()->numberBetween(4, 10),
            "max_age" => $this->faker->optional()->numberBetween(11, 18),
            "capacity" => $this->faker->optional()->numberBetween(5, 30),
            "price_from" => $this->faker->optional()->randomFloat(2, 500, 2500),
            "price_to" => $this->faker->optional()->randomFloat(2, 2500, 5000),
            "currency" => "RUB",
            "status" => $this->faker->randomElement([
                "draft",
                "pending_review",
                "published",
                "archived",
            ]),
            "is_featured" => false,
            "published_at" => null,
        ];
    }

    public function forOrganizationLocation(
        Organization|string $organization,
        OrganizationLocation|string $location,
    ): self {
        return $this->state(
            fn() => [
                "organization_id" =>
                    $organization instanceof Organization
                        ? (string) $organization->getKey()
                        : $organization,
                "location_id" =>
                    $location instanceof OrganizationLocation
                        ? (string) $location->getKey()
                        : $location,
            ],
        );
    }

    public function published(): self
    {
        return $this->state(
            fn() => [
                "status" => "published",
                "published_at" => now(),
            ],
        );
    }

    public function featured(): self
    {
        return $this->state(
            fn() => [
                "is_featured" => true,
            ],
        );
    }
}
