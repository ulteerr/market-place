<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DistrictResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "name" => $this->name,
            "city_id" => (string) $this->city_id,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
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
