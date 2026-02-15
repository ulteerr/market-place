<?php

declare(strict_types=1);

namespace Modules\Users\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AccessPermissionResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "code" => (string) $this->code,
            "scope" => (string) $this->scope,
            "label" => $this->label,
        ];
    }
}
