<?php

declare(strict_types=1);

namespace Modules\Activities\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Files\Http\Resources\FileResource;

final class ActivityResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "organization_id" => (string) $this->organization_id,
            "location_id" => (string) $this->location_id,
            "name" => $this->name,
            "slug" => $this->slug,
            "short_description" => $this->short_description,
            "description" => $this->description,
            "min_age" => $this->min_age,
            "max_age" => $this->max_age,
            "capacity" => $this->capacity,
            "price_from" => $this->price_from,
            "price_to" => $this->price_to,
            "currency" => $this->currency,
            "status" => $this->status,
            "is_featured" => (bool) $this->is_featured,
            "published_at" => $this->published_at,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "organization" => $this->whenLoaded("organization", function () {
                if (!$this->organization) {
                    return null;
                }

                return [
                    "id" => (string) $this->organization->id,
                    "name" => $this->organization->name,
                ];
            }),
            "location" => $this->whenLoaded("location", function () {
                if (!$this->location) {
                    return null;
                }

                return [
                    "id" => (string) $this->location->id,
                    "organization_id" => (string) $this->location->organization_id,
                    "city_id" => $this->location->city_id
                        ? (string) $this->location->city_id
                        : null,
                    "address" => $this->location->address,
                    "city" =>
                        $this->location->relationLoaded("city") && $this->location->city
                            ? [
                                "id" => (string) $this->location->city->id,
                                "name" => $this->location->city->name,
                            ]
                            : null,
                ];
            }),
            "primary_category" => $this->whenLoaded("primaryCategory", function () {
                $category = $this->primaryCategory->first();
                if (!$category) {
                    return null;
                }

                return [
                    "id" => (string) $category->id,
                    "name" => $category->name,
                    "slug" => $category->slug,
                    "parent_id" => $category->parent_id ? (string) $category->parent_id : null,
                    "parent" =>
                        $category->relationLoaded("parent") && $category->parent
                            ? [
                                "id" => (string) $category->parent->id,
                                "name" => $category->parent->name,
                                "slug" => $category->parent->slug,
                            ]
                            : null,
                ];
            }),
            "schedules" => $this->whenLoaded("schedules", function () {
                return $this->schedules
                    ->map(
                        fn($item) => [
                            "id" => (string) $item->id,
                            "day_of_week" => (int) $item->day_of_week,
                            "start_time" => $item->start_time,
                            "end_time" => $item->end_time,
                        ],
                    )
                    ->values();
            }),
            "cover" => $this->whenLoaded(
                "cover",
                fn() => $this->cover ? (new FileResource($this->cover))->resolve() : null,
            ),
            "gallery" => $this->whenLoaded(
                "gallery",
                fn() => FileResource::collection($this->orderedGalleryFiles())->resolve(),
            ),
        ];
    }
}
