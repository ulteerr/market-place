<?php

declare(strict_types=1);

namespace Modules\Categories\Tests\Feature;

use App\Shared\Testing\AdminCrudTestCase;
use Illuminate\Support\Facades\DB;
use Modules\Activities\Models\Activity;
use Modules\Categories\Models\Category;
use Modules\Users\Models\Role;
use PHPUnit\Framework\Attributes\Test;

final class AdminCategoriesCrudTest extends AdminCrudTestCase
{
    protected function endpoint(): string
    {
        return "/api/admin/categories";
    }

    protected function table(): string
    {
        return "categories";
    }

    protected function seedForList(int $count): void
    {
        for ($index = 0; $index <= $count; $index += 1) {
            Category::factory()->create([
                "name" => "Категория {$index}",
                "slug" => "kategoriya-{$index}",
                "sort_order" => $index,
            ]);
        }
    }

    protected function seedForCreate(): void
    {
        // no-op
    }

    protected function createPayload(): array
    {
        return [
            "name" => "Спорт",
            "slug" => "sport",
            "parent_id" => null,
            "sort_order" => 10,
            "is_active" => true,
        ];
    }

    protected function createDatabaseHas(): array
    {
        return [
            "name" => "Спорт",
            "slug" => "sport",
            "parent_id" => null,
            "sort_order" => 10,
            "is_active" => true,
        ];
    }

    protected function createItem(): mixed
    {
        return Category::factory()->create([
            "name" => "Музыка",
            "slug" => "muzyka",
            "sort_order" => 20,
            "is_active" => true,
        ]);
    }

    protected function itemId(mixed $item): string
    {
        return (string) $item->id;
    }

    protected function showIdPath(): string
    {
        return "data.id";
    }

    protected function updatePayload(mixed $item): array
    {
        return [
            "name" => "Музыка updated",
            "slug" => "muzyka-updated",
            "sort_order" => 25,
            "is_active" => false,
        ];
    }

    protected function updateDatabaseHas(mixed $item): array
    {
        return [
            "id" => (string) $item->id,
            "name" => "Музыка updated",
            "slug" => "muzyka-updated",
            "sort_order" => 25,
            "is_active" => false,
        ];
    }

    protected function actingAsAdmin(): array
    {
        $adminRole = Role::factory()->admin()->create();

        $auth = $this->actingAsUser();
        $auth["user"]->roles()->sync([$adminRole->id]);

        return $auth;
    }

    #[Test]
    public function admin_can_preview_unique_slug_within_parent_scope(): void
    {
        $auth = $this->actingAsAdmin();
        $root = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "parent_id" => null,
        ]);
        Category::factory()
            ->childOf($root)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
            ]);

        $this->withHeaders($auth["headers"])
            ->getJson("/api/admin/categories/slug-preview?name=Футбол&parent_id=" . $root->id)
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("data.slug", "futbol-2");
    }

    #[Test]
    public function admin_cannot_create_duplicate_slug_inside_same_parent(): void
    {
        $auth = $this->actingAsAdmin();
        $root = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "parent_id" => null,
        ]);
        Category::factory()
            ->childOf($root)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
            ]);

        $this->withHeaders($auth["headers"])
            ->postJson($this->endpoint(), [
                "name" => "Футбол 2",
                "slug" => "futbol",
                "parent_id" => (string) $root->id,
                "sort_order" => 30,
                "is_active" => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["slug"]);
    }

    #[Test]
    public function admin_cannot_create_category_under_non_root_parent(): void
    {
        $auth = $this->actingAsAdmin();
        $root = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "parent_id" => null,
        ]);
        $leaf = Category::factory()
            ->childOf($root)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
            ]);

        $this->withHeaders($auth["headers"])
            ->postJson($this->endpoint(), [
                "name" => "Мини-футбол",
                "slug" => "mini-futbol",
                "parent_id" => (string) $leaf->id,
                "sort_order" => 30,
                "is_active" => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["parent_id"]);
    }

    #[Test]
    public function admin_cannot_move_category_under_its_own_descendant(): void
    {
        $auth = $this->actingAsAdmin();
        $root = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "parent_id" => null,
        ]);
        $child = Category::factory()
            ->childOf($root)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
            ]);

        $this->withHeaders($auth["headers"])
            ->patchJson($this->endpoint() . "/" . $root->id, [
                "parent_id" => (string) $child->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["parent_id"]);
    }

    #[Test]
    public function admin_cannot_delete_category_with_children(): void
    {
        $auth = $this->actingAsAdmin();
        $root = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
        ]);
        Category::factory()
            ->childOf($root)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
            ]);

        $this->withHeaders($auth["headers"])
            ->deleteJson($this->endpoint() . "/" . $root->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors(["category"]);
    }

    #[Test]
    public function admin_cannot_delete_category_assigned_to_activity(): void
    {
        $auth = $this->actingAsAdmin();
        $root = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
        ]);
        $leaf = Category::factory()
            ->childOf($root)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
            ]);
        $activity = Activity::factory()->create();

        DB::table("activity_categories")->insert([
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $leaf->id,
        ]);

        $this->withHeaders($auth["headers"])
            ->deleteJson($this->endpoint() . "/" . $leaf->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors(["category"]);
    }
}
