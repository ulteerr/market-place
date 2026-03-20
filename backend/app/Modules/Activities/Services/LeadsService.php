<?php

declare(strict_types=1);

namespace Modules\Activities\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Activities\Models\Activity;
use Modules\Activities\Models\Lead;
use Modules\Activities\Repositories\ActivitiesRepositoryInterface;
use Modules\Activities\Repositories\LeadsRepositoryInterface;
use Modules\Children\Models\Child;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Repositories\OrganizationsRepositoryInterface;
use Modules\Users\Models\User;
use RuntimeException;

final class LeadsService
{
    public function __construct(
        private readonly LeadsRepositoryInterface $repository,
        private readonly ActivitiesRepositoryInterface $activitiesRepository,
        private readonly OrganizationsRepositoryInterface $organizationsRepository,
    ) {}

    public function submit(string $activityId, User $actor, array $payload): Lead
    {
        $activity = $this->findActivityOrFail($activityId);

        $requestForType = trim((string) ($payload["request_for_type"] ?? ""));
        $childId = $this->normalizeOptionalString($payload["child_id"] ?? null);
        $contactChannels = $this->normalizeChannels($payload["contact_channels"] ?? []);
        $contactPayload = $this->normalizeContactPayload($payload["contact_payload"] ?? null);

        if ($contactChannels === []) {
            throw ValidationException::withMessages([
                "contact_channels" => "At least one contact channel is required.",
            ]);
        }

        if (!$this->hasReachableContact($contactChannels, $contactPayload)) {
            throw ValidationException::withMessages([
                "contact_payload" =>
                    "At least one real contact is required for the selected contact channels.",
            ]);
        }

        $resolvedChildId = null;
        if ($requestForType === Lead::REQUEST_FOR_CHILD) {
            if ($childId === null) {
                throw ValidationException::withMessages([
                    "child_id" => "Child is required for child lead.",
                ]);
            }

            $child = Child::query()->find($childId);
            if (!$child) {
                throw ValidationException::withMessages([
                    "child_id" => "Child not found.",
                ]);
            }

            if ((string) $child->user_id !== (string) $actor->id) {
                throw ValidationException::withMessages([
                    "child_id" => 'User cannot create a lead for another user\'s child.',
                ]);
            }

            $resolvedChildId = (string) $child->id;
        } elseif ($requestForType !== Lead::REQUEST_FOR_SELF) {
            throw ValidationException::withMessages([
                "request_for_type" => "Invalid request_for_type.",
            ]);
        }

        return $this->repository
            ->create([
                "activity_id" => (string) $activity->id,
                "user_id" => (string) $actor->id,
                "child_id" => $resolvedChildId,
                "request_for_type" => $requestForType,
                "contact_channels" => $contactChannels,
                "contact_payload" => $contactPayload,
                "message" => $this->normalizeOptionalString($payload["message"] ?? null),
                "status" => Lead::STATUS_NEW,
            ])
            ->fresh(["activity.organization", "user", "child"]) ??
            throw new RuntimeException("Lead create failed");
    }

    public function listForOrganization(
        string $organizationId,
        User $actor,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        $organization = $this->findOrganizationOrFail($organizationId);
        Gate::forUser($actor)->authorize("viewActivityLeads", $organization);

        return $this->repository->paginateForOrganization($organizationId, $perPage, $filters);
    }

    public function listForAdmin(
        User $actor,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        if (
            !$actor->hasPermission("admin.panel.access") ||
            !$actor->hasPermission("admin.activity-leads.read")
        ) {
            throw new RuntimeException("Forbidden");
        }

        return $this->repository->paginateAdmin($perPage, $filters);
    }

    public function updateStatusForOrganization(
        string $organizationId,
        string $leadId,
        User $actor,
        string $status,
    ): Lead {
        $organization = $this->findOrganizationOrFail($organizationId);
        Gate::forUser($actor)->authorize("reviewActivityLeads", $organization);

        $lead = $this->repository->findByIdAndOrganization($leadId, $organizationId);
        if (!$lead) {
            throw new RuntimeException("Lead not found");
        }

        return $this->repository
            ->update($lead, [
                "status" => $this->normalizeStatus($status),
            ])
            ->fresh(["activity.organization", "user", "child"]) ?? $lead;
    }

    public function updateStatusForAdmin(User $actor, string $leadId, string $status): Lead
    {
        if (
            !$actor->hasPermission("admin.panel.access") ||
            !$actor->hasPermission("admin.activity-leads.update")
        ) {
            throw new RuntimeException("Forbidden");
        }

        $lead = $this->repository->findById($leadId);
        if (!$lead) {
            throw new RuntimeException("Lead not found");
        }

        return $this->repository
            ->update($lead, [
                "status" => $this->normalizeStatus($status),
            ])
            ->fresh(["activity.organization", "user", "child"]) ?? $lead;
    }

    private function findActivityOrFail(string $activityId): Activity
    {
        $activity = $this->activitiesRepository->findById($activityId);
        if (!$activity) {
            throw new RuntimeException("Activity not found");
        }

        return $activity;
    }

    private function findOrganizationOrFail(string $organizationId): Organization
    {
        $organization = $this->organizationsRepository->findById($organizationId);
        if (!$organization) {
            throw new RuntimeException("Organization not found");
        }

        return $organization;
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeChannels(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $allowed = ["any", "chat", "phone", "telegram", "whatsapp", "max"];

        return array_values(
            array_unique(
                array_filter(
                    array_map(static function ($item) use ($allowed): string {
                        $normalized = trim((string) $item);

                        return in_array($normalized, $allowed, true) ? $normalized : "";
                    }, $value),
                ),
            ),
        );
    }

    /**
     * @param mixed $value
     * @return array<string, string>|null
     */
    private function normalizeContactPayload(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $result = [];
        foreach (["phone", "telegram", "whatsapp", "max"] as $key) {
            $normalized = $this->normalizeOptionalString($value[$key] ?? null);
            if ($normalized !== null) {
                $result[$key] = $normalized;
            }
        }

        return $result === [] ? null : $result;
    }

    /**
     * @param array<int, string> $channels
     * @param array<string, string>|null $payload
     */
    private function hasReachableContact(array $channels, ?array $payload): bool
    {
        if (in_array("chat", $channels, true) && count($channels) === 1) {
            return true;
        }

        if (in_array("any", $channels, true)) {
            return $payload !== null && $payload !== [];
        }

        if ($payload === null) {
            return false;
        }

        foreach (["phone", "telegram", "whatsapp", "max"] as $key) {
            if (in_array($key, $channels, true) && isset($payload[$key]) && $payload[$key] !== "") {
                return true;
            }
        }

        return false;
    }

    private function normalizeStatus(string $status): string
    {
        $normalized = trim($status);
        $allowed = [
            Lead::STATUS_NEW,
            Lead::STATUS_IN_PROGRESS,
            Lead::STATUS_CONTACTED,
            Lead::STATUS_REGISTERED,
            Lead::STATUS_CANCELLED,
        ];

        if (!in_array($normalized, $allowed, true)) {
            throw ValidationException::withMessages([
                "status" => "Invalid lead status.",
            ]);
        }

        return $normalized;
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === "" ? null : $value;
    }
}
