<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrganizationLocationMetroStationResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "metro_station_id" => (string) $this->metro_station_id,
            "travel_mode" => $this->travel_mode,
            "duration_minutes" => $this->duration_minutes,
            "metro_station" => $this->whenLoaded("metroStation", function () {
                if (!$this->metroStation) {
                    return null;
                }

                return [
                    "id" => (string) $this->metroStation->id,
                    "name" => $this->metroStation->name,
                    "city_id" => (string) $this->metroStation->city_id,
                    "metro_line_id" => (string) $this->metroStation->metro_line_id,
                    "metro_line" =>
                        $this->metroStation->relationLoaded("metroLine") &&
                        $this->metroStation->metroLine
                            ? [
                                "name" => $this->metroStation->metroLine->name,
                                "color" => $this->metroStation->metroLine->color,
                            ]
                            : null,
                ];
            }),
        ];
    }
}
