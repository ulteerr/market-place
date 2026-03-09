<?php

declare(strict_types=1);

namespace Modules\Organizations\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Children\Models\Child;
use Modules\Organizations\Models\OrganizationClient;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationJoinRequest;
use Modules\Organizations\Repositories\OrganizationJoinRequestsRepositoryInterface;
use Modules\Organizations\Repositories\OrganizationsRepositoryInterface;
use Modules\Users\Models\User;
use RuntimeException;

final class OrganizationJoinRequestsService
{
    public function __construct(
        private readonly OrganizationJoinRequestsRepositoryInterface $repository,
        private readonly OrganizationsRepositoryInterface $organizationsRepository,
    ) {}

    public function submit(
        string $organizationId,
        User $actor,
        string $subjectType,
        string $subjectId,
        ?string $message = null,
    ): OrganizationJoinRequest {
        $organization = $this->findOrganizationOrFail($organizationId);
        $normalizedSubjectType = trim($subjectType);
        $normalizedSubjectId = trim($subjectId);

        if ($normalizedSubjectType === OrganizationJoinRequest::SUBJECT_TYPE_USER) {
            if ($normalizedSubjectId !== (string) $actor->id) {
                throw ValidationException::withMessages([
                    "subject_id" => "User can submit request only for himself.",
                ]);
            }
        } elseif ($normalizedSubjectType === OrganizationJoinRequest::SUBJECT_TYPE_CHILD) {
            $child = Child::query()->find($normalizedSubjectId);
            if (!$child) {
                throw ValidationException::withMessages([
                    "subject_id" => "Child not found.",
                ]);
            }

            if ((string) $child->user_id !== (string) $actor->id) {
                throw ValidationException::withMessages([
                    "subject_id" => "User cannot submit request for another user's child.",
                ]);
            }
        } else {
            throw ValidationException::withMessages([
                "subject_type" => "Invalid subject type.",
            ]);
        }

        $isActiveClient = OrganizationClient::query()
            ->where("organization_id", (string) $organization->id)
            ->where("subject_type", $normalizedSubjectType)
            ->where("subject_id", $normalizedSubjectId)
            ->where("status", "active")
            ->exists();

        if ($isActiveClient) {
            if ($normalizedSubjectType === OrganizationJoinRequest::SUBJECT_TYPE_CHILD) {
                throw ValidationException::withMessages([
                    "organization_id" => "Child is already participating in this organization.",
                ]);
            }

            throw ValidationException::withMessages([
                "organization_id" => "User is already participating in this organization.",
            ]);
        }

        $pendingRequest = $this->repository->findPendingByOrganizationAndSubject(
            (string) $organization->id,
            $normalizedSubjectType,
            $normalizedSubjectId,
        );

        if ($pendingRequest) {
            throw ValidationException::withMessages([
                "organization_id" => "Pending join request already exists.",
            ]);
        }

        return $this->repository->create([
            "organization_id" => (string) $organization->id,
            "subject_type" => $normalizedSubjectType,
            "subject_id" => $normalizedSubjectId,
            "requested_by_user_id" => (string) $actor->id,
            "status" => "pending",
            "message" => $message,
        ]);
    }

    public function listForOrganization(
        string $organizationId,
        User $actor,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        $organization = $this->findOrganizationOrFail($organizationId);
        Gate::forUser($actor)->authorize("viewJoinRequests", $organization);

        return $this->repository->paginateForOrganization(
            (string) $organization->id,
            $perPage,
            $filters,
        );
    }

    public function myRequests(
        string $organizationId,
        User $actor,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        $organization = $this->findOrganizationOrFail($organizationId);

        return $this->repository->paginateForOrganizationAndRequester(
            (string) $organization->id,
            (string) $actor->id,
            $perPage,
            $filters,
        );
    }

    public function approve(
        string $organizationId,
        string $joinRequestId,
        User $actor,
        ?string $reviewNote = null,
    ): OrganizationJoinRequest {
        $organization = $this->findOrganizationOrFail($organizationId);
        Gate::forUser($actor)->authorize("reviewJoinRequests", $organization);

        $request = $this->findJoinRequestOrFail((string) $organization->id, $joinRequestId);

        DB::transaction(function () use ($request, $actor, $reviewNote): void {
            $this->repository->update($request, [
                "status" => "approved",
                "review_note" => $reviewNote,
                "reviewed_by_user_id" => (string) $actor->id,
                "reviewed_at" => now(),
            ]);

            OrganizationClient::query()->updateOrCreate(
                [
                    "organization_id" => (string) $request->organization_id,
                    "subject_type" => (string) $request->subject_type,
                    "subject_id" => (string) $request->subject_id,
                ],
                [
                    "status" => "active",
                    "added_by_user_id" => (string) $actor->id,
                    "joined_at" => now(),
                ],
            );
        });

        return $request->fresh(["requestedBy", "subjectUser", "subjectChild", "reviewedBy"]) ??
            $request;
    }

    public function reject(
        string $organizationId,
        string $joinRequestId,
        User $actor,
        ?string $reviewNote = null,
    ): OrganizationJoinRequest {
        $organization = $this->findOrganizationOrFail($organizationId);
        Gate::forUser($actor)->authorize("reviewJoinRequests", $organization);

        $request = $this->findJoinRequestOrFail((string) $organization->id, $joinRequestId);
        DB::transaction(function () use ($request, $actor, $reviewNote): void {
            $this->repository->update($request, [
                "status" => "rejected",
                "review_note" => $reviewNote,
                "reviewed_by_user_id" => (string) $actor->id,
                "reviewed_at" => now(),
            ]);

            OrganizationClient::query()
                ->where("organization_id", (string) $request->organization_id)
                ->where("subject_type", (string) $request->subject_type)
                ->where("subject_id", (string) $request->subject_id)
                ->delete();
        });

        return $request->fresh(["requestedBy", "subjectUser", "subjectChild", "reviewedBy"]) ??
            $request;
    }

    private function findOrganizationOrFail(string $organizationId): Organization
    {
        $organization = $this->organizationsRepository->findById($organizationId);
        if (!$organization) {
            throw new RuntimeException("Organization not found");
        }

        return $organization;
    }

    private function findJoinRequestOrFail(
        string $organizationId,
        string $joinRequestId,
    ): OrganizationJoinRequest {
        $request = $this->repository->findByIdAndOrganization($joinRequestId, $organizationId);
        if (!$request) {
            throw new RuntimeException("Join request not found");
        }

        return $request;
    }
}
