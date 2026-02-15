<?php
declare(strict_types=1);
namespace Modules\Users\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Users\Enums\RoleCode;
use Modules\Users\Models\Role;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            "id" => (string) Str::uuid(),
            "code" => $this->faker->unique()->word(),
            "label" => $this->faker->word(),
        ];
    }

    public function participant(): static
    {
        return $this->state(
            fn() => [
                "code" => RoleCode::PARTICIPANT->value,
                "label" => "Участник",
            ],
        );
    }

    public function admin(): static
    {
        return $this->state(
            fn() => [
                "code" => RoleCode::ADMIN->value,
                "label" => "Администратор",
            ],
        );
    }

    public function superAdmin(): static
    {
        return $this->state(
            fn() => [
                "code" => RoleCode::SUPER_ADMIN->value,
                "label" => "Супер-администратор",
            ],
        );
    }

    public function moderator(): static
    {
        return $this->state(
            fn() => [
                "code" => RoleCode::MODERATOR->value,
                "label" => "Модератор",
            ],
        );
    }
}
