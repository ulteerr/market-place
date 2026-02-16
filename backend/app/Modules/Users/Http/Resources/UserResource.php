<?php

declare(strict_types=1);

namespace Modules\Users\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Files\Http\Resources\FileResource;

final class UserResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => $this->id,
            "email" => $this->email,
            "first_name" => $this->first_name,
            "last_name" => $this->last_name,
            "middle_name" => $this->middle_name,
            "gender" => $this->gender,
            "settings" => $this->settings ?? (object) [],
            "avatar" => $this->relationLoaded("avatar")
                ? ($this->avatar
                    ? new FileResource($this->avatar)
                    : null)
                : null,
            "roles" => $this->whenLoaded("roles", function () {
                return $this->roles->pluck("code")->values();
            }),
            "permissions" => $this->when(
                $request->is("api/me") || $request->is("api/auth/*"),
                fn() => $this->effectivePermissionCodes(),
            ),
            "permission_overrides" => $this->whenLoaded("permissionOverrides", function () {
                $allow = [];
                $deny = [];

                foreach ($this->permissionOverrides as $override) {
                    $code = (string) ($override->permission?->code ?? "");
                    if ($code === "") {
                        continue;
                    }

                    if ((bool) $override->allowed) {
                        $allow[] = $code;
                    } else {
                        $deny[] = $code;
                    }
                }

                return [
                    "allow" => array_values(array_unique($allow)),
                    "deny" => array_values(array_unique($deny)),
                ];
            }),
            "is_admin" => $this->whenLoaded("roles", fn() => $this->isAdmin()),
            "can_access_admin_panel" => $this->whenLoaded(
                "roles",
                fn() => $this->canAccessAdminPanel(),
            ),
        ];
    }
}
