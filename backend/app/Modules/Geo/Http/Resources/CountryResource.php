<?php

declare(strict_types=1);

namespace Modules\Geo\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CountryResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "name" => $this->name,
            "iso_code" => $this->iso_code,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
