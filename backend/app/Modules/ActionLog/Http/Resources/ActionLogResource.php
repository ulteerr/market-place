<?php

declare(strict_types=1);

namespace Modules\ActionLog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Users\Models\User;

final class ActionLogResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        $user = $this->whenLoaded("user");
        $userPayload = null;

        if ($user instanceof User) {
            $fullName = trim(
                implode(
                    " ",
                    array_filter([$user->last_name, $user->first_name, $user->middle_name]),
                ),
            );

            $userPayload = [
                "id" => (string) $user->id,
                "full_name" => $fullName !== "" ? $fullName : $user->email,
                "email" => $user->email,
            ];
        } elseif ($this->user_id !== null) {
            $userPayload = [
                "id" => (string) $this->user_id,
                "full_name" => null,
                "email" => null,
            ];
        }

        return [
            "id" => (string) $this->id,
            "user_id" => $this->user_id,
            "user" => $userPayload,
            "event" => $this->event,
            "model_type" => $this->model_type,
            "model_id" => $this->model_id,
            "ip_address" => $this->ip_address,
            "before" => $this->before,
            "after" => $this->after,
            "changed_fields" => $this->changed_fields,
            "created_at" => $this->created_at,
        ];
    }
}
