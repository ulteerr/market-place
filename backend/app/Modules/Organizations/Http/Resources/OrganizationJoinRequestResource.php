<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Organizations\Models\OrganizationJoinRequest;
use Modules\Users\Models\User;
use Modules\Children\Models\Child;

final class OrganizationJoinRequestResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "organization_id" => (string) $this->organization_id,
            "subject_type" => (string) $this->subject_type,
            "subject_id" => (string) $this->subject_id,
            "status" => (string) $this->status,
            "message" => $this->message,
            "review_note" => $this->review_note,
            "reviewed_at" => $this->reviewed_at?->toIso8601String(),
            "created_at" => $this->created_at?->toIso8601String(),
            "updated_at" => $this->updated_at?->toIso8601String(),
            "subject" => $this->resolveSubject(),
            "requested_by" => $this->resolveUser($this->requestedBy),
            "reviewed_by" => $this->resolveUser($this->reviewedBy),
        ];
    }

    private function resolveSubject(): ?array
    {
        if ((string) $this->subject_type === OrganizationJoinRequest::SUBJECT_TYPE_USER) {
            return $this->resolveSubjectUser($this->subjectUser);
        }

        if ((string) $this->subject_type === OrganizationJoinRequest::SUBJECT_TYPE_CHILD) {
            return $this->resolveSubjectChild($this->subjectChild);
        }

        return null;
    }

    private function resolveSubjectUser(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            "type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
            "id" => (string) $user->id,
            "first_name" => $user->first_name,
            "last_name" => $user->last_name,
            "middle_name" => $user->middle_name,
            "email" => $user->email,
            "label" =>
                $this->buildPersonLabel($user->first_name, $user->last_name, $user->middle_name) ?:
                $user->email,
        ];
    }

    private function resolveSubjectChild(?Child $child): ?array
    {
        if (!$child) {
            return null;
        }

        return [
            "type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
            "id" => (string) $child->id,
            "first_name" => $child->first_name,
            "last_name" => $child->last_name,
            "middle_name" => $child->middle_name,
            "user_id" => (string) $child->user_id,
            "label" => $this->buildPersonLabel(
                $child->first_name,
                $child->last_name,
                $child->middle_name,
            ),
        ];
    }

    private function resolveUser(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            "id" => (string) $user->id,
            "first_name" => $user->first_name,
            "last_name" => $user->last_name,
            "middle_name" => $user->middle_name,
            "email" => $user->email,
            "label" =>
                $this->buildPersonLabel($user->first_name, $user->last_name, $user->middle_name) ?:
                $user->email,
        ];
    }

    private function buildPersonLabel(
        ?string $firstName,
        ?string $lastName,
        ?string $middleName,
    ): string {
        return trim(implode(" ", array_filter([$lastName, $firstName, $middleName])));
    }
}
