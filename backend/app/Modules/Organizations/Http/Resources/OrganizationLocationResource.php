<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrganizationLocationResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "country_id" => $this->country_id ? (string) $this->country_id : null,
            "region_id" => $this->region_id ? (string) $this->region_id : null,
            "city_id" => $this->city_id ? (string) $this->city_id : null,
            "district_id" => $this->district_id ? (string) $this->district_id : null,
            "address" => $this->address,
            "lat" => $this->lat,
            "lng" => $this->lng,
            "metro_connections" => $this->whenLoaded(
                "metroConnections",
                fn() => OrganizationLocationMetroStationResource::collection(
                    $this->metroConnections,
                ),
            ),
        ];
    }
}
