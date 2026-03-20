<?php

declare(strict_types=1);

namespace Modules\Activities\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Activities\Models\Lead;
use Modules\Children\Models\Child;
use Modules\Users\Models\User;

final class LeadResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "activity_id" => (string) $this->activity_id,
            "user_id" => (string) $this->user_id,
            "child_id" => $this->child_id ? (string) $this->child_id : null,
            "request_for_type" => (string) $this->request_for_type,
            "status" => (string) $this->status,
            "contact_channels" => $this->contact_channels ?? [],
            "contact_payload" => $this->contact_payload,
            "message" => $this->message,
            "created_at" => $this->created_at?->toIso8601String(),
            "updated_at" => $this->updated_at?->toIso8601String(),
            "activity" => $this->whenLoaded("activity", function () {
                if (!$this->activity) {
                    return null;
                }

                return [
                    "id" => (string) $this->activity->id,
                    "name" => $this->activity->name,
                    "slug" => $this->activity->slug,
                    "organization" =>
                        $this->activity->relationLoaded("organization") &&
                        $this->activity->organization
                            ? [
                                "id" => (string) $this->activity->organization->id,
                                "name" => $this->activity->organization->name,
                            ]
                            : null,
                ];
            }),
            "subject" => $this->resolveSubject(),
        ];
    }

    private function resolveSubject(): ?array
    {
        if ((string) $this->request_for_type === Lead::REQUEST_FOR_SELF) {
            return $this->resolveUser($this->user);
        }

        if ((string) $this->request_for_type === Lead::REQUEST_FOR_CHILD) {
            return $this->resolveChild($this->child);
        }

        return null;
    }

    private function resolveUser(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            "type" => Lead::REQUEST_FOR_SELF,
            "id" => (string) $user->id,
            "first_name" => $user->first_name,
            "last_name" => $user->last_name,
            "middle_name" => $user->middle_name,
            "email" => $user->email,
            "phone" => $user->phone,
            "label" => trim(
                implode(
                    " ",
                    array_filter([$user->last_name, $user->first_name, $user->middle_name]),
                ),
            ),
        ];
    }

    private function resolveChild(?Child $child): ?array
    {
        if (!$child) {
            return null;
        }

        return [
            "type" => Lead::REQUEST_FOR_CHILD,
            "id" => (string) $child->id,
            "user_id" => (string) $child->user_id,
            "first_name" => $child->first_name,
            "last_name" => $child->last_name,
            "middle_name" => $child->middle_name,
            "birth_date" => $child->birth_date,
            "label" => trim(
                implode(
                    " ",
                    array_filter([$child->last_name, $child->first_name, $child->middle_name]),
                ),
            ),
        ];
    }
}
