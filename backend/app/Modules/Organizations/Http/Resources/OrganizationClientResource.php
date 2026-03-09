<?php

declare(strict_types=1);

namespace Modules\Organizations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Organizations\Models\OrganizationJoinRequest;

final class OrganizationClientResource extends JsonResource
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
            "added_by_user_id" => $this->added_by_user_id ? (string) $this->added_by_user_id : null,
            "joined_at" => $this->joined_at?->toIso8601String(),
            "created_at" => $this->created_at?->toIso8601String(),
            "updated_at" => $this->updated_at?->toIso8601String(),
            "subject" => $this->resolveSubject(),
            "added_by" => $this->resolveAddedBy(),
        ];
    }

    private function resolveSubject(): ?array
    {
        if ((string) $this->subject_type === OrganizationJoinRequest::SUBJECT_TYPE_USER) {
            if (!$this->subjectUser) {
                return null;
            }

            return [
                "type" => OrganizationJoinRequest::SUBJECT_TYPE_USER,
                "id" => (string) $this->subjectUser->id,
                "first_name" => $this->subjectUser->first_name,
                "last_name" => $this->subjectUser->last_name,
                "middle_name" => $this->subjectUser->middle_name,
                "email" => $this->subjectUser->email,
                "label" =>
                    trim(
                        implode(
                            " ",
                            array_filter([
                                $this->subjectUser->last_name,
                                $this->subjectUser->first_name,
                                $this->subjectUser->middle_name,
                            ]),
                        ),
                    ) ?:
                    $this->subjectUser->email,
            ];
        }

        if ((string) $this->subject_type === OrganizationJoinRequest::SUBJECT_TYPE_CHILD) {
            if (!$this->subjectChild) {
                return null;
            }

            return [
                "type" => OrganizationJoinRequest::SUBJECT_TYPE_CHILD,
                "id" => (string) $this->subjectChild->id,
                "first_name" => $this->subjectChild->first_name,
                "last_name" => $this->subjectChild->last_name,
                "middle_name" => $this->subjectChild->middle_name,
                "user_id" => (string) $this->subjectChild->user_id,
                "label" => trim(
                    implode(
                        " ",
                        array_filter([
                            $this->subjectChild->last_name,
                            $this->subjectChild->first_name,
                            $this->subjectChild->middle_name,
                        ]),
                    ),
                ),
            ];
        }

        return null;
    }

    private function resolveAddedBy(): ?array
    {
        if (!$this->addedBy) {
            return null;
        }

        return [
            "id" => (string) $this->addedBy->id,
            "first_name" => $this->addedBy->first_name,
            "last_name" => $this->addedBy->last_name,
            "middle_name" => $this->addedBy->middle_name,
            "email" => $this->addedBy->email,
            "label" =>
                trim(
                    implode(
                        " ",
                        array_filter([
                            $this->addedBy->last_name,
                            $this->addedBy->first_name,
                            $this->addedBy->middle_name,
                        ]),
                    ),
                ) ?:
                $this->addedBy->email,
        ];
    }
}
