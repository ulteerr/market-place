<?php

declare(strict_types=1);

namespace Modules\Users\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Users\Models\User;

final class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name'  => $this->faker->lastName(),
            'middle_name' => null,
            'email'      => $this->faker->unique()->safeEmail(),
            'password'         => 'password123',
            'phone'      => $this->faker->optional()->phoneNumber(),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): self
    {
        return $this->state(fn() => [
            'email_verified_at' => null,
        ]);
    }
}
