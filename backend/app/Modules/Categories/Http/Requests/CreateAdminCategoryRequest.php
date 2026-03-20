<?php

declare(strict_types=1);

namespace Modules\Categories\Http\Requests;

final class CreateAdminCategoryRequest extends CategoryCrudRequest
{
    protected function ruleset(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "slug" => $this->categorySlugRules(true),
            "parent_id" => ["nullable", "uuid", "exists:categories,id"],
            "sort_order" => ["nullable", "integer", "min:0"],
            "is_active" => ["nullable", "boolean"],
        ];
    }
}
