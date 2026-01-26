<?php

declare(strict_types=1);

namespace Modules\Users\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'email'      => $this->email,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('code')->values();
            }),
            'is_admin' => $this->whenLoaded('roles', fn() => $this->isAdmin()),
            'can_access_admin_panel' => $this->whenLoaded(
                'roles',
                fn() => $this->canAccessAdminPanel()
            ),
        ];
    }
}
