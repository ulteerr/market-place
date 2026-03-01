<?php

declare(strict_types=1);

namespace Modules\Metro\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MetroLineResource extends JsonResource
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
            "color" => $this->color,
            "city_id" => (string) $this->city_id,
            "source" => $this->source,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
