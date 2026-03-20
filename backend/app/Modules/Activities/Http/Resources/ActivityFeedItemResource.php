<?php

declare(strict_types=1);

namespace Modules\Activities\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Files\Http\Resources\FileResource;

final class ActivityFeedItemResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "public_key" => $this->buildPublicKey(),
            "name" => $this->name,
            "slug" => $this->slug,
            "short_description" => $this->short_description,
            "min_age" => $this->min_age,
            "max_age" => $this->max_age,
            "price_from" => $this->price_from,
            "price_to" => $this->price_to,
            "currency" => $this->currency,
            "is_featured" => (bool) $this->is_featured,
            "published_at" => $this->published_at,
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
                    "city" =>
                        $this->location->relationLoaded("city") && $this->location->city
                            ? [
                                "id" => (string) $this->location->city->id,
                                "name" => $this->location->city->name,
                            ]
                            : null,
                    "address" => $this->location->address,
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
            "cover" => $this->whenLoaded(
                "cover",
                fn() => $this->cover ? (new FileResource($this->cover))->resolve() : null,
            ),
        ];
    }

    private function buildPublicKey(): string
    {
        return sprintf("%s-%s", (string) $this->slug, (string) $this->id);
    }
}
