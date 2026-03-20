<?php

declare(strict_types=1);

namespace Modules\Categories\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Categories\Models\Category;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CategoryPublicReadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_roots_returns_only_active_root_categories_by_default(): void
    {
        Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "parent_id" => null,
            "is_active" => true,
        ]);
        Category::factory()->create([
            "name" => "Архив",
            "slug" => "archive",
            "parent_id" => null,
            "is_active" => false,
        ]);

        $this->getJson("/api/categories/roots")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data")
            ->assertJsonPath("data.0.slug", "sport");
    }

    #[Test]
    public function public_roots_can_include_inactive_categories_when_requested(): void
    {
        Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "parent_id" => null,
            "is_active" => true,
        ]);
        Category::factory()->create([
            "name" => "Архив",
            "slug" => "archive",
            "parent_id" => null,
            "is_active" => false,
        ]);

        $this->getJson("/api/categories/roots?only_active=false")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(2, "data");
    }

    #[Test]
    public function public_tree_returns_only_active_categories_by_default(): void
    {
        $activeRoot = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "parent_id" => null,
            "is_active" => true,
        ]);
        Category::factory()
            ->childOf($activeRoot)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
                "is_active" => true,
            ]);
        Category::factory()->create([
            "name" => "Архив",
            "slug" => "archive",
            "parent_id" => null,
            "is_active" => false,
        ]);

        $this->getJson("/api/categories/tree")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(1, "data")
            ->assertJsonPath("data.0.slug", "sport")
            ->assertJsonPath("data.0.children.0.slug", "futbol");
    }

    #[Test]
    public function public_tree_can_include_inactive_categories_when_requested(): void
    {
        Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "parent_id" => null,
            "is_active" => true,
        ]);
        Category::factory()->create([
            "name" => "Архив",
            "slug" => "archive",
            "parent_id" => null,
            "is_active" => false,
        ]);

        $this->getJson("/api/categories/tree?only_active=false")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(2, "data");
    }

    #[Test]
    public function public_children_endpoint_returns_children_for_requested_parent(): void
    {
        $sport = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "parent_id" => null,
        ]);
        Category::factory()
            ->childOf($sport)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
            ]);
        Category::factory()
            ->childOf($sport)
            ->create([
                "name" => "Волейбол",
                "slug" => "volejbol",
            ]);

        $this->getJson("/api/categories/" . $sport->id . "/children")
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonCount(2, "data");
    }
}
