<?php

declare(strict_types=1);

namespace Modules\Categories\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Categories\Models\Category;
use Modules\Categories\Repositories\CategoriesRepository;
use Modules\Categories\Services\CategoriesService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CategoriesTreeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function repository_returns_tree_sorted_by_root_and_child_sort_order_then_name(): void
    {
        $repository = new CategoriesRepository();

        $rootB = Category::factory()->create([
            "name" => "Творчество",
            "slug" => "tvorchestvo",
            "sort_order" => 20,
            "parent_id" => null,
            "is_active" => true,
        ]);
        $rootA = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "sort_order" => 10,
            "parent_id" => null,
            "is_active" => true,
        ]);

        Category::factory()
            ->childOf($rootA)
            ->create([
                "name" => "Волейбол",
                "slug" => "volejbol",
                "sort_order" => 20,
                "is_active" => true,
            ]);
        Category::factory()
            ->childOf($rootA)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
                "sort_order" => 10,
                "is_active" => true,
            ]);
        Category::factory()
            ->childOf($rootB)
            ->create([
                "name" => "Музыка",
                "slug" => "muzyka",
                "sort_order" => 10,
                "is_active" => true,
            ]);

        $tree = $repository->getTree(true);

        $this->assertCount(2, $tree);
        $this->assertSame("sport", $tree[0]->slug);
        $this->assertSame("tvorchestvo", $tree[1]->slug);
        $this->assertSame(["futbol", "volejbol"], $tree[0]->children->pluck("slug")->all());
        $this->assertSame(["muzyka"], $tree[1]->children->pluck("slug")->all());
    }

    #[Test]
    public function service_cannot_deactivate_parent_with_active_children(): void
    {
        $service = new CategoriesService(new CategoriesRepository());

        $root = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
            "parent_id" => null,
            "is_active" => true,
        ]);
        Category::factory()
            ->childOf($root)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
                "is_active" => true,
            ]);

        $this->expectException(ValidationException::class);

        $service->update((string) $root->id, [
            "is_active" => false,
        ]);
    }
}
