<?php

declare(strict_types=1);

namespace Modules\ActionLog\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\ActionLog\Models\ActionLog;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;

final class ActionLogService
{
    public function logModelEvent(
        Model $model,
        string $event,
        ?array $before,
        ?array $after,
        ?array $changedFields = null,
    ): ActionLog {
        return ActionLog::query()->create([
            "user_id" => auth()->id(),
            "event" => $event,
            "model_type" => $model::class,
            "model_id" => (string) $model->getKey(),
            "ip_address" => request()?->ip(),
            "before" => $before,
            "after" => $after,
            "changed_fields" => $changedFields !== null ? array_values($changedFields) : null,
        ]);
    }

    public function paginateForAdmin(
        array $filters = [],
        int $perPage = 20,
        ?int $page = null,
    ): LengthAwarePaginator {
        $query = ActionLog::query()->with("user");

        $event = trim((string) ($filters["event"] ?? ""));
        if (in_array($event, ["create", "update", "delete"], true)) {
            $query->where("event", $event);
        }

        $model = trim((string) ($filters["model"] ?? ""));
        if ($model !== "") {
            $normalizedModel = mb_strtolower($model);
            $modelAliases = [
                "user" => User::class,
                "users" => User::class,
                "role" => Role::class,
                "roles" => Role::class,
            ];

            if (isset($modelAliases[$normalizedModel])) {
                $query->where("model_type", $modelAliases[$normalizedModel]);
            } else {
                $query->whereRaw("LOWER(model_type) LIKE ?", ["%" . $normalizedModel . "%"]);
            }
        }

        $userFilter = trim((string) ($filters["user"] ?? ""));
        if ($userFilter !== "") {
            $tokens = preg_split("/\s+/u", $userFilter, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if ($tokens === []) {
                $tokens = [$userFilter];
            }

            $query->where(function ($builder) use ($tokens): void {
                foreach ($tokens as $token) {
                    $builder->where(function ($tokenBuilder) use ($token): void {
                        $tokenBuilder->orWhereHas("user", function ($userQuery) use ($token): void {
                            $userQuery
                                ->where("email", "like", "%" . $token . "%")
                                ->orWhere("first_name", "like", "%" . $token . "%")
                                ->orWhere("last_name", "like", "%" . $token . "%")
                                ->orWhere("middle_name", "like", "%" . $token . "%");
                        });

                        if ($this->isUuid($token)) {
                            $tokenBuilder
                                ->orWhere("user_id", $token)
                                ->orWhereHas("user", function ($userQuery) use ($token): void {
                                    $userQuery->where("id", $token);
                                });
                        }
                    });
                }
            });
        }

        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where("model_type", "like", "%" . $search . "%")
                    ->orWhere("model_id", "like", "%" . $search . "%")
                    ->orWhere("event", "like", "%" . $search . "%")
                    ->orWhereHas("user", function ($userQuery) use ($search): void {
                        $userQuery
                            ->where("email", "like", "%" . $search . "%")
                            ->orWhere("first_name", "like", "%" . $search . "%")
                            ->orWhere("last_name", "like", "%" . $search . "%")
                            ->orWhere("middle_name", "like", "%" . $search . "%");
                    });
            });
        }

        $dateFrom = $this->parseDate($filters["date_from"] ?? null, false);
        if ($dateFrom !== null) {
            $query->where("created_at", ">=", $dateFrom);
        }

        $dateTo = $this->parseDate($filters["date_to"] ?? null, true);
        if ($dateTo !== null) {
            $query->where("created_at", "<=", $dateTo);
        }

        $allowedSorts = ["created_at", "event", "model_type", "user_id"];
        $sortBy = (string) ($filters["sort_by"] ?? "created_at");
        $sortDir = strtolower((string) ($filters["sort_dir"] ?? "desc")) === "asc" ? "asc" : "desc";
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = "created_at";
        }

        $query->orderBy($sortBy, $sortDir)->orderByDesc("created_at");

        $safePerPage = max(1, min(100, $perPage));
        $safePage = max(1, (int) ($page ?? 1));

        return $query->paginate($safePerPage, ["*"], "page", $safePage);
    }

    private function parseDate(mixed $value, bool $endOfDay): ?CarbonImmutable
    {
        if (!is_string($value) || trim($value) === "") {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($value);
            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function isUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        );
    }
}
