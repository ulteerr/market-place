<?php

declare(strict_types=1);

namespace Modules\ChangeLog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Users\Models\User;

final class ChangeLogResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        $actor = $this->whenLoaded("actor");
        $actorPayload = null;

        if ($actor instanceof User) {
            $fullName = trim(
                implode(
                    " ",
                    array_filter([$actor->last_name, $actor->first_name, $actor->middle_name]),
                ),
            );

            $actorPayload = [
                "id" => (string) $actor->id,
                "full_name" => $fullName !== "" ? $fullName : $actor->email,
            ];
        } elseif ($this->actor_id !== null) {
            $actorPayload = [
                "id" => (string) $this->actor_id,
                "full_name" => null,
            ];
        }

        return [
            "id" => $this->id,
            "auditable_type" => $this->auditable_type,
            "auditable_id" => $this->auditable_id,
            "event" => $this->event,
            "version" => $this->version,
            "before" => $this->before,
            "after" => $this->after,
            "changed_fields" => $this->changed_fields,
            "actor_type" => $this->actor_type,
            "actor_id" => $this->actor_id,
            "actor" => $actorPayload,
            "batch_id" => $this->batch_id,
            "rolled_back_from_id" => $this->rolled_back_from_id,
            "meta" => $this->meta,
            "created_at" => $this->created_at,
        ];
    }
}
