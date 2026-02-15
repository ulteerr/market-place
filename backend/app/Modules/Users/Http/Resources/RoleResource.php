<?php

declare(strict_types=1);

namespace Modules\Users\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RoleResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => $this->id,
            "code" => $this->code,
            "label" => $this->label,
            "is_system" => $this->is_system,
            "permissions" => $this->whenLoaded(
                "permissions",
                fn() => $this->permissions->pluck("code")->values(),
            ),
        ];
    }
}
