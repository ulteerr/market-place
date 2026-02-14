<?php

declare(strict_types=1);

namespace Modules\ChangeLog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\ChangeLog\Models\ChangeLog;
use RuntimeException;

final class ChangeLogService
{
    public function paginate(
        array $filters = [],
        ?int $perPage = null,
        ?int $page = null,
    ): LengthAwarePaginator {
        $query = ChangeLog::query()->with("actor")->orderByDesc("created_at");

        $model = trim((string) ($filters["model"] ?? ""));
        $entityId = trim((string) ($filters["entity_id"] ?? ""));
        $event = trim((string) ($filters["event"] ?? ""));

        if ($model !== "") {
            $resolvedType = $this->resolveModelType($model);
            $query->where("auditable_type", $resolvedType);
        }

        if ($entityId !== "") {
            $query->where("auditable_id", $entityId);
        }

        if ($event !== "") {
            $query->where("event", $event);
        }

        $mode = $this->listMode();
        $maxPerPage = max(1, (int) config("changelog.admin.max_per_page", 200));

        if ($mode === "latest") {
            $latestLimit = max(
                1,
                min($maxPerPage, (int) config("changelog.admin.latest_limit", 20)),
            );
            return $query->paginate($latestLimit, ["*"], "page", 1);
        }

        $defaultPerPage = max(
            1,
            min($maxPerPage, (int) config("changelog.admin.default_per_page", 30)),
        );
        $resolvedPerPage = $perPage !== null ? (int) $perPage : $defaultPerPage;
        $resolvedPerPage = max(1, min($maxPerPage, $resolvedPerPage));
        $resolvedPage = max(1, (int) ($page ?? 1));

        return $query->paginate($resolvedPerPage, ["*"], "page", $resolvedPage);
    }

    public function listMode(): string
    {
        $mode = (string) config("changelog.admin.list_mode", "latest");
        return in_array($mode, ["latest", "paginated"], true) ? $mode : "latest";
    }

    public function findById(string $id): ?ChangeLog
    {
        return ChangeLog::query()->with("actor")->find($id);
    }

    public function rollback(ChangeLog $entry): Model
    {
        $modelClass = $this->assertModelAllowed($entry->auditable_type);
        $prototype = new $modelClass();
        $currentSignature = method_exists($prototype, "changeLogSchemaSignature")
            ? $prototype->changeLogSchemaSignature()
            : null;
        $entrySignature = Arr::get($entry->meta, "schema_signature");

        if (
            $entrySignature !== null &&
            $currentSignature !== null &&
            $entrySignature !== $currentSignature
        ) {
            throw new RuntimeException("Cannot rollback: model schema signature mismatch.");
        }

        $targetState = $this->resolveTargetState($entry);
        $keyName = $prototype->getKeyName();
        $entityId = $entry->auditable_id;

        /** @var Model|null $model */
        $model = $this->findModel($modelClass, $entityId);

        return DB::transaction(function () use (
            $entry,
            $modelClass,
            $model,
            $targetState,
            $keyName,
            $entityId,
        ) {
            $context = app(ChangeLogContext::class);

            return $context->withMeta(
                [
                    "rolled_back_from_id" => $entry->id,
                    "rolled_back_from_version" => $entry->version,
                    "rolled_back_to_version" => $this->resolveRollbackTargetVersion($entry),
                    "rollback" => true,
                ],
                function () use ($modelClass, $model, $targetState, $keyName, $entityId): Model {
                    if ($targetState === null) {
                        if ($model !== null) {
                            $model->delete();
                            return $model;
                        }

                        throw new RuntimeException(
                            "Cannot rollback to empty state: model already absent.",
                        );
                    }

                    $instance = $model ?? new $modelClass();
                    $payload = $this->prepareRollbackPayload($instance, $targetState);

                    $payload[$keyName] = $entityId;
                    $instance->forceFill($payload);
                    $instance->save();

                    if (
                        $this->supportsSoftDeletes($instance) &&
                        method_exists($instance, "trashed") &&
                        $instance->trashed()
                    ) {
                        $instance->restore();
                    }

                    return $instance->refresh();
                },
            );
        });
    }

    private function resolveModelType(string $model): string
    {
        $map = config("changelog.models", []);

        if (isset($map[$model])) {
            return (string) $map[$model];
        }

        return $this->assertModelAllowed($model);
    }

    private function assertModelAllowed(string $modelType): string
    {
        $allowed = array_values(config("changelog.models", []));
        if (!in_array($modelType, $allowed, true)) {
            throw new RuntimeException("Model is not allowed for changelog operations.");
        }

        return $modelType;
    }

    private function resolveTargetState(ChangeLog $entry): ?array
    {
        return match ($entry->event) {
            "create" => $entry->after,
            "update", "delete", "restore" => $entry->before,
            default => throw new RuntimeException("Unsupported changelog event for rollback."),
        };
    }

    private function resolveRollbackTargetVersion(ChangeLog $entry): ?int
    {
        return match ($entry->event) {
            "create" => 1,
            "update", "delete", "restore" => max(1, ((int) $entry->version) - 1),
            default => null,
        };
    }

    private function prepareRollbackPayload(Model $model, array $snapshot): array
    {
        $fillable = method_exists($model, "changeLogRollbackAttributes")
            ? $model->changeLogRollbackAttributes()
            : $model->getFillable();

        $allowed = array_fill_keys($fillable, true);
        $allowed[$model->getKeyName()] = true;

        return array_filter(
            $snapshot,
            fn(string $key) => isset($allowed[$key]),
            ARRAY_FILTER_USE_KEY,
        );
    }

    private function findModel(string $modelClass, string $id): ?Model
    {
        /** @var Builder $query */
        $query = $modelClass::query();

        if ($this->supportsSoftDeletes(new $modelClass())) {
            $query = $query->withTrashed();
        }

        return $query->find($id);
    }

    private function supportsSoftDeletes(Model $model): bool
    {
        return in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive($model),
            true,
        );
    }
}
