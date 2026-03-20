<?php

declare(strict_types=1);

namespace Modules\Categories\Http\Requests;

use App\Shared\Http\Requests\CrudRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Categories\Models\Category;

abstract class CategoryCrudRequest extends CrudRequest
{
    protected function categorySlugRules(bool $required): array
    {
        $parentId = $this->normalizedParentId();
        $currentId = $this->routeCategoryId();

        $uniqueRule = Rule::unique("categories", "slug")
            ->ignore($currentId)
            ->where(function ($query) use ($parentId): void {
                if ($parentId === null) {
                    $query->whereNull("parent_id");

                    return;
                }

                $query->where("parent_id", $parentId);
            });

        return array_values(
            array_filter([$required ? "required" : "sometimes", "string", "max:255", $uniqueRule]),
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (!$this->has("parent_id")) {
                return;
            }

            $parentId = $this->normalizedParentId();
            if ($parentId === null) {
                return;
            }

            $currentId = $this->routeCategoryId();
            if ($currentId !== null && $parentId === $currentId) {
                $validator->errors()->add("parent_id", "Category cannot be its own parent.");

                return;
            }

            $parent = Category::query()
                ->select(["id", "parent_id"])
                ->find($parentId);
            if (!$parent instanceof Category) {
                return;
            }

            if ($parent->parent_id !== null) {
                $validator
                    ->errors()
                    ->add("parent_id", "Category nesting deeper than two levels is not allowed.");
            }

            if ($currentId === null) {
                return;
            }

            $ancestor = $parent;
            while ($ancestor !== null) {
                if ((string) $ancestor->id === $currentId) {
                    $validator
                        ->errors()
                        ->add("parent_id", "Category cannot be moved under its own descendant.");

                    return;
                }

                if ($ancestor->parent_id === null) {
                    return;
                }

                $ancestor = Category::query()
                    ->select(["id", "parent_id"])
                    ->find((string) $ancestor->parent_id);
            }
        });
    }

    protected function routeCategoryId(): ?string
    {
        $value = $this->route("id");

        if (!is_string($value) || trim($value) === "") {
            return null;
        }

        return trim($value);
    }

    protected function normalizedParentId(): ?string
    {
        if (!$this->has("parent_id")) {
            return null;
        }

        $value = $this->input("parent_id");
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === "" ? null : $value;
    }
}
