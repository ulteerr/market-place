<?php

declare(strict_types=1);

namespace Modules\Categories\Http\Requests;

final class UpdateAdminCategoryRequest extends CategoryCrudRequest
{
    protected function ruleset(): array
    {
        return [
            "name" => ["sometimes", "string", "max:255"],
            "slug" => $this->categorySlugRules(false),
            "parent_id" => ["nullable", "uuid", "exists:categories,id"],
            "sort_order" => ["sometimes", "integer", "min:0"],
            "is_active" => ["sometimes", "boolean"],
        ];
    }
}
