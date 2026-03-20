<?php

declare(strict_types=1);

namespace Modules\Activities\Http\Requests;

final class CreateAdminActivityRequest extends ActivityCrudRequest
{
    protected function ruleset(): array
    {
        return [
            "organization_id" => ["required", "uuid", "exists:organizations,id"],
            "location_id" => ["required", "uuid", "exists:organization_locations,id"],
            "category_id" => ["required", "uuid", "exists:categories,id"],
            "name" => ["required", "string", "max:255"],
            "slug" => ["required", "string", "max:255"],
            "short_description" => ["required", "string", "max:255"],
            "description" => ["nullable", "string"],
            "min_age" => ["nullable", "integer", "min:0"],
            "max_age" => ["nullable", "integer", "min:0", "gte:min_age"],
            "capacity" => ["nullable", "integer", "min:0"],
            "price_from" => ["nullable", "numeric", "min:0"],
            "price_to" => ["nullable", "numeric", "min:0", "gte:price_from"],
            "currency" => ["nullable", "string", "max:5"],
            "status" => ["nullable", "string", "in:draft,pending_review,published,archived"],
            "is_featured" => ["nullable", "boolean"],
            "published_at" => ["nullable", "date"],
            ...$this->scheduleRules(),
            "cover" => $this->imageRules(),
            "cover_delete" => ["nullable", "boolean"],
            "gallery" => ["nullable", "array"],
            "gallery.*" => $this->imageRules(),
            "gallery_delete_ids" => ["nullable", "array"],
            "gallery_delete_ids.*" => ["uuid", "distinct"],
            "gallery_order_ids" => ["nullable", "array"],
            "gallery_order_ids.*" => ["uuid", "distinct"],
        ];
    }
}
