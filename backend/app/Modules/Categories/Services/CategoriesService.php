<?php

declare(strict_types=1);

namespace Modules\Categories\Services;

use App\Shared\Support\Transliteration;
use App\Shared\Traits\HasDictionaryCrudOperations;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Categories\Models\Category;
use Modules\Categories\Repositories\CategoriesRepositoryInterface;

final class CategoriesService
{
    use HasDictionaryCrudOperations;

    public function __construct(private readonly CategoriesRepositoryInterface $repository) {}

    protected function entityNotFoundMessage(): string
    {
        return "Category not found";
    }

    protected function deleteEntity(object $entity): void
    {
        assert($entity instanceof Category);
        $this->assertCanDelete($entity);
        $this->repository->delete($entity);
    }

    public function createCategory(array $data): Category
    {
        $this->assertParentConstraints(null, $data);

        return $this->repository->create($data);
    }

    public function create(array $data): Category
    {
        return $this->createCategory($data);
    }

    public function updateCategory(Category $category, array $data): Category
    {
        $this->assertParentConstraints($category, $data);
        $this->assertCanDeactivate($category, $data);

        return $this->repository->update($category, $data);
    }

    public function update(string $id, array $data): Category
    {
        $entity = $this->findByIdOrFail($id);
        assert($entity instanceof Category);

        return $this->updateCategory($entity, $data);
    }

    public function getCategoryById(string $id): ?Category
    {
        return $this->repository->findById($id);
    }

    public function findById(string $id): ?Category
    {
        return $this->getCategoryById($id);
    }

    public function getRoots(bool $onlyActive = false): Collection
    {
        return $this->repository->findRoots($onlyActive);
    }

    public function getChildren(string $parentId, bool $onlyActive = false): Collection
    {
        return $this->repository->findChildren($parentId, $onlyActive);
    }

    public function getTree(bool $onlyActive = false): Collection
    {
        return $this->repository->getTree($onlyActive);
    }

    public function paginate(
        int $perPage = 20,
        array $with = [],
        array $filters = [],
    ): LengthAwarePaginator {
        return $this->repository->paginate($perPage, $with, $filters);
    }

    public function previewSlug(
        string $name,
        ?string $parentId = null,
        ?string $ignoreId = null,
    ): string {
        $baseSlug = Transliteration::slug($name);
        if ($baseSlug === "") {
            $baseSlug = "category";
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($slug, $parentId, $ignoreId)) {
            $slug = sprintf("%s-%d", $baseSlug, $suffix);
            $suffix += 1;
        }

        return $slug;
    }

    public function delete(Category $category): bool
    {
        $this->assertCanDelete($category);

        return $this->repository->delete($category);
    }

    private function assertParentConstraints(?Category $category, array $data): void
    {
        if (!array_key_exists("parent_id", $data)) {
            return;
        }

        $parentId = $this->normalizeParentId($data["parent_id"] ?? null);
        if ($parentId === null) {
            return;
        }

        $parent = Category::query()
            ->select(["id", "parent_id"])
            ->find($parentId);
        if (!$parent instanceof Category) {
            return;
        }

        if ($category !== null && (string) $category->id === (string) $parent->id) {
            throw ValidationException::withMessages([
                "parent_id" => "Category cannot be its own parent.",
            ]);
        }

        if ($parent->parent_id !== null) {
            throw ValidationException::withMessages([
                "parent_id" => "Category nesting deeper than two levels is not allowed.",
            ]);
        }

        if ($category === null) {
            return;
        }

        $ancestor = $parent;
        while ($ancestor !== null) {
            if ((string) $ancestor->id === (string) $category->id) {
                throw ValidationException::withMessages([
                    "parent_id" => "Category cannot be moved under its own descendant.",
                ]);
            }

            if ($ancestor->parent_id === null) {
                return;
            }

            $ancestor = Category::query()
                ->select(["id", "parent_id"])
                ->find((string) $ancestor->parent_id);
        }
    }

    private function assertCanDeactivate(Category $category, array $data): void
    {
        if (!array_key_exists("is_active", $data)) {
            return;
        }

        if ((bool) $data["is_active"] !== false) {
            return;
        }

        $hasActiveChildren = $category->children()->where("is_active", true)->exists();

        if ($hasActiveChildren) {
            throw ValidationException::withMessages([
                "is_active" => "Cannot deactivate category while it has active child categories.",
            ]);
        }
    }

    private function assertCanDelete(Category $category): void
    {
        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                "category" => "Cannot delete category with child categories.",
            ]);
        }

        if ($category->activities()->exists()) {
            throw ValidationException::withMessages([
                "category" => "Cannot delete category assigned to activities.",
            ]);
        }
    }

    private function normalizeParentId(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === "" ? null : $value;
    }

    private function slugExists(string $slug, ?string $parentId, ?string $ignoreId): bool
    {
        $query = Category::query()->where("slug", $slug);

        if ($parentId === null) {
            $query->whereNull("parent_id");
        } else {
            $query->where("parent_id", $parentId);
        }

        if ($ignoreId !== null && $ignoreId !== "") {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
