<?php

declare(strict_types=1);

namespace Modules\Categories\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CategoryResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "name" => $this->name,
            "slug" => $this->slug,
            "parent_id" => $this->parent_id !== null ? (string) $this->parent_id : null,
            "sort_order" => (int) $this->sort_order,
            "is_active" => (bool) $this->is_active,
            "children_count" => isset($this->children_count)
                ? (int) $this->children_count
                : ($this->relationLoaded("children")
                    ? $this->children->count()
                    : null),
            "activities_count" => isset($this->activities_count)
                ? (int) $this->activities_count
                : null,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "parent" => $this->whenLoaded("parent", function () {
                if (!$this->parent) {
                    return null;
                }

                return [
                    "id" => (string) $this->parent->id,
                    "name" => $this->parent->name,
                    "slug" => $this->parent->slug,
                ];
            }),
            "children" => $this->whenLoaded("children", fn() => self::collection($this->children)),
        ];
    }
}
