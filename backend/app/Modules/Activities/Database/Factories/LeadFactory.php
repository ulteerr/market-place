<?php

declare(strict_types=1);

namespace Modules\Activities\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Activities\Models\Activity;
use Modules\Activities\Models\Lead;
use Modules\Children\Database\Factories\ChildFactory;
use Modules\Children\Models\Child;
use Modules\Users\Database\Factories\UserFactory;
use Modules\Users\Models\User;

/**
 * @extends Factory<Lead>
 */
final class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            "activity_id" => Activity::factory()->published(),
            "user_id" => User::factory(),
            "child_id" => null,
            "request_for_type" => "self",
            "contact_channels" => ["phone"],
            "contact_payload" => [
                "phone" => $this->faker->e164PhoneNumber(),
            ],
            "message" => $this->faker->optional()->sentence(),
            "status" => "new",
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

    public function forUser(User|string $user): self
    {
        return $this->state(
            fn() => [
                "user_id" => $user instanceof User ? (string) $user->getKey() : $user,
            ],
        );
    }

    public function selfRequest(): self
    {
        return $this->state(
            fn() => [
                "child_id" => null,
                "request_for_type" => "self",
            ],
        );
    }

    public function childRequest(Child|string|null $child = null): self
    {
        return $this->state(function (array $attributes) use ($child): array {
            if ($child instanceof Child) {
                return [
                    "user_id" => (string) $child->user_id,
                    "child_id" => (string) $child->getKey(),
                    "request_for_type" => "child",
                ];
            }

            if (is_string($child) && $child !== "") {
                return [
                    "child_id" => $child,
                    "request_for_type" => "child",
                ];
            }

            $userId = (string) ($attributes["user_id"] ?? UserFactory::new()->create()->getKey());
            $generatedChild = ChildFactory::new()->create(["user_id" => $userId]);

            return [
                "user_id" => $userId,
                "child_id" => (string) $generatedChild->getKey(),
                "request_for_type" => "child",
            ];
        });
    }

    public function viaPhone(): self
    {
        return $this->state(
            fn() => [
                "contact_channels" => ["phone"],
                "contact_payload" => [
                    "phone" => fake()->e164PhoneNumber(),
                ],
            ],
        );
    }

    public function viaMessenger(string $channel = "telegram"): self
    {
        return $this->state(
            fn() => [
                "contact_channels" => [$channel],
                "contact_payload" => [
                    $channel => "@" . fake()->userName(),
                ],
            ],
        );
    }

    public function allowAnyContact(): self
    {
        return $this->state(
            fn() => [
                "contact_channels" => ["any"],
                "contact_payload" => [
                    "phone" => fake()->e164PhoneNumber(),
                    "telegram" => "@" . fake()->userName(),
                ],
            ],
        );
    }

    public function inProgress(): self
    {
        return $this->state(fn() => ["status" => "in_progress"]);
    }

    public function contacted(): self
    {
        return $this->state(fn() => ["status" => "contacted"]);
    }
}
