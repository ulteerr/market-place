<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MetroStationResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "name" => $this->name,
            "external_id" => $this->external_id,
            "line_id" => $this->line_id,
            "geo_lat" => $this->geo_lat,
            "geo_lon" => $this->geo_lon,
            "is_closed" => $this->is_closed,
            "metro_line_id" => (string) $this->metro_line_id,
            "city_id" => (string) $this->city_id,
            "source" => $this->source,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "metro_line" => $this->whenLoaded("metroLine", function () {
                if (!$this->metroLine) {
                    return null;
                }

                return [
                    "name" => $this->metroLine->name,
                    "color" => $this->metroLine->color,
                ];
            }),
            "city" => $this->whenLoaded("city", function () {
                if (!$this->city) {
                    return null;
                }

                return [
                    "name" => $this->city->name,
                ];
            }),
        ];
    }
}
