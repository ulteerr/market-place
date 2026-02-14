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
    public function paginate(array $filters = [], int $perPage = 30): LengthAwarePaginator
    {
        $query = ChangeLog::query()->orderByDesc("created_at");

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

        return $query->paginate(max(1, min(200, $perPage)));
    }

    public function findById(string $id): ?ChangeLog
    {
        return ChangeLog::query()->find($id);
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
            "create", "update", "restore" => $entry->after,
            "delete" => $entry->before,
            default => throw new RuntimeException("Unsupported changelog event for rollback."),
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
