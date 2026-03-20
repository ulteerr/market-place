<?php

declare(strict_types=1);

namespace Modules\Activities\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Activities\Models\Lead;

final class LeadsRepository implements LeadsRepositoryInterface
{
    public function create(array $data): Lead
    {
        return Lead::query()->create($data);
    }

    public function update(Lead $lead, array $data): Lead
    {
        $lead->update($data);

        return $lead;
    }

    public function findById(string $id): ?Lead
    {
        return Lead::query()->with($this->relations())->find($id);
    }

    public function findByIdAndOrganization(string $leadId, string $organizationId): ?Lead
    {
        return Lead::query()
            ->with($this->relations())
            ->whereKey($leadId)
            ->whereHas("activity", function (Builder $query) use ($organizationId): void {
                $query->where("organization_id", $organizationId);
            })
            ->first();
    }

    public function paginateForOrganization(
        string $organizationId,
        int $perPage = 20,
        array $filters = [],
    ): LengthAwarePaginator {
        $query = Lead::query()
            ->with($this->relations())
            ->whereHas("activity", function (Builder $builder) use ($organizationId): void {
                $builder->where("organization_id", $organizationId);
            });

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters);

        return $query->paginate($perPage);
    }

    public function paginateAdmin(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = Lead::query()->with($this->relations());

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            "activity:id,organization_id,location_id,name,slug,status",
            "activity.organization:id,name,owner_user_id",
            "user:id,first_name,last_name,middle_name,email,phone",
            "child:id,user_id,first_name,last_name,middle_name,birth_date",
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $status = trim((string) ($filters["status"] ?? ""));
        if ($status !== "") {
            $query->where("status", $status);
        }

        $requestForType = trim((string) ($filters["request_for_type"] ?? ""));
        if ($requestForType !== "") {
            $query->where("request_for_type", $requestForType);
        }

        $activityId = trim((string) ($filters["activity_id"] ?? ""));
        if ($activityId !== "") {
            $query->where("activity_id", $activityId);
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $like = "%" . $search . "%";

            $query->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where("message", "like", $like)
                    ->orWhereHas("activity", function (Builder $activityBuilder) use ($like): void {
                        $activityBuilder->where("name", "like", $like);
                    })
                    ->orWhereHas("user", function (Builder $userBuilder) use ($like): void {
                        $userBuilder
                            ->where("first_name", "like", $like)
                            ->orWhere("last_name", "like", $like)
                            ->orWhere("email", "like", $like)
                            ->orWhere("phone", "like", $like);
                    })
                    ->orWhereHas("child", function (Builder $childBuilder) use ($like): void {
                        $childBuilder
                            ->where("first_name", "like", $like)
                            ->orWhere("last_name", "like", $like);
                    });
            });
        }
    }

    private function applySort(Builder $query, array $filters): void
    {
        $sortBy = trim((string) ($filters["sort_by"] ?? "created_at"));
        $sortDir = strtolower(trim((string) ($filters["sort_dir"] ?? "desc")));

        if (!in_array($sortDir, ["asc", "desc"], true)) {
            $sortDir = "desc";
        }

        $allowed = ["created_at", "updated_at", "status"];
        if (!in_array($sortBy, $allowed, true)) {
            $sortBy = "created_at";
        }

        $query->orderBy($sortBy, $sortDir)->orderByDesc("id");
    }
}
