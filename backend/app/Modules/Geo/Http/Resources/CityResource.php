<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CityResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "name" => $this->name,
            "country_id" => (string) $this->country_id,
            "region_id" => (string) $this->region_id,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "country" => $this->whenLoaded("country", function () {
                if (!$this->country) {
                    return null;
                }

                return [
                    "name" => $this->country->name,
                ];
            }),
            "region" => $this->whenLoaded("region", function () {
                if (!$this->region) {
                    return null;
                }

                return [
                    "name" => $this->region->name,
                ];
            }),
        ];
    }
}
