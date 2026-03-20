<?php

declare(strict_types=1);

namespace Modules\Categories\Database\Factories;

use App\Shared\Support\Transliteration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Categories\Models\Category;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            "name" => mb_convert_case($name, MB_CASE_TITLE, "UTF-8"),
            "slug" => Transliteration::slug($name),
            "parent_id" => null,
            "sort_order" => $this->faker->numberBetween(10, 100),
            "is_active" => true,
        ];
    }

    public function root(): self
    {
        return $this->state(
            fn(): array => [
                "parent_id" => null,
            ],
        );
    }

    public function childOf(Category|string $parent): self
    {
        $parentId = $parent instanceof Category ? (string) $parent->id : (string) $parent;

        return $this->state(
            fn(): array => [
                "parent_id" => $parentId,
            ],
        );
    }

    public function inactive(): self
    {
        return $this->state(
            fn(): array => [
                "is_active" => false,
            ],
        );
    }
}
