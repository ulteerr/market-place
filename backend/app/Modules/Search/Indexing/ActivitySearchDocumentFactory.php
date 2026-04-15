<?php

declare(strict_types=1);

namespace Modules\Search\Indexing;

use Modules\Activities\Models\Activity;

final class ActivitySearchDocumentFactory
{
    /**
     * @return array<string, mixed>
     */
    public function make(Activity $activity): array
    {
        $activity->loadMissing([
            "organization:id,name",
            "location:id,city_id,address",
            "location.city:id,name",
            "primaryCategory:id,name,slug,parent_id",
            "primaryCategory.parent:id,name,slug",
        ]);

        $category = $activity->primaryCategory->first();
        $rootCategory = $category?->parent;

        return [
            "id" => (string) $activity->id,
            "name" => (string) $activity->name,
            "slug" => (string) $activity->slug,
            "short_description" => (string) ($activity->short_description ?? ""),
            "description" => (string) ($activity->description ?? ""),
            "organization_id" => (string) $activity->organization_id,
            "organization" => [
                "id" => (string) ($activity->organization?->id ?? ""),
                "name" => (string) ($activity->organization?->name ?? ""),
            ],
            "location_id" => (string) $activity->location_id,
            "location" => [
                "id" => (string) ($activity->location?->id ?? ""),
                "address" => (string) ($activity->location?->address ?? ""),
            ],
            "city_id" => (string) ($activity->location?->city_id ?? ""),
            "city" => [
                "id" => (string) ($activity->location?->city?->id ?? ""),
                "name" => (string) ($activity->location?->city?->name ?? ""),
            ],
            "category_id" => (string) ($category?->id ?? ""),
            "category" => [
                "id" => (string) ($category?->id ?? ""),
                "name" => (string) ($category?->name ?? ""),
                "slug" => (string) ($category?->slug ?? ""),
            ],
            "root_category_id" => (string) ($rootCategory?->id ?? ""),
            "root_category" => [
                "id" => (string) ($rootCategory?->id ?? ""),
                "name" => (string) ($rootCategory?->name ?? ""),
                "slug" => (string) ($rootCategory?->slug ?? ""),
            ],
            "status" => (string) $activity->status,
            "is_featured" => (bool) $activity->is_featured,
            "published_at" => $activity->published_at?->toAtomString(),
            "created_at" => $activity->created_at?->toAtomString(),
            "updated_at" => $activity->updated_at?->toAtomString(),
        ];
    }
}
