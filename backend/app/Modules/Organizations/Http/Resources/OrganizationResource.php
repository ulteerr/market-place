<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrganizationResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "name" => $this->name,
            "description" => $this->description,
            "address" => $this->relationLoaded("locations")
                ? $this->locations
                    ->pluck("address")
                    ->first(fn(mixed $value): bool => is_string($value) && trim($value) !== "")
                : null,
            "phone" => $this->phone,
            "email" => $this->email,
            "status" => $this->status,
            "source_type" => $this->source_type,
            "ownership_status" => $this->ownership_status,
            "owner_user_id" => $this->owner_user_id ? (string) $this->owner_user_id : null,
            "created_by_user_id" => $this->created_by_user_id
                ? (string) $this->created_by_user_id
                : null,
            "claimed_at" => $this->claimed_at,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "owner" => $this->whenLoaded("owner", function () {
                if (!$this->owner) {
                    return null;
                }

                return [
                    "id" => (string) $this->owner->id,
                    "first_name" => $this->owner->first_name,
                    "last_name" => $this->owner->last_name,
                    "middle_name" => $this->owner->middle_name,
                    "email" => $this->owner->email,
                ];
            }),
            "locations" => $this->whenLoaded(
                "locations",
                fn() => OrganizationLocationResource::collection($this->locations),
            ),
        ];
    }
}
