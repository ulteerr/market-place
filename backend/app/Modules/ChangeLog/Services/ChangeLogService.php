<?php

declare(strict_types=1);

namespace Modules\ChangeLog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\ChangeLog\Models\ChangeLog;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use RuntimeException;

final class ChangeLogService
{
    public function paginate(
        array $filters = [],
        ?int $perPage = null,
        ?int $page = null,
    ): LengthAwarePaginator {
        $query = ChangeLog::query()->with("actor");

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

        if ($model !== "" && $entityId !== "") {
            $query->orderByDesc("version");
        } else {
            $query->orderByDesc("created_at");
        }

        $mode = $this->listMode();
        $limit = max(1, (int) config("changelog.admin.limit", 20));

        if ($mode === "latest") {
            return $query->paginate($limit, ["*"], "page", 1);
        }

        $resolvedPerPage = $perPage !== null ? (int) $perPage : $limit;
        $resolvedPerPage = max(1, min($limit, $resolvedPerPage));
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
                    $batchId = app(ChangeLogContext::class)->currentBatchId();

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
                    $beforeRelatedState = $this->buildRelatedSnapshot($instance);
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

                    $this->applyRelatedRollbackState($instance, $targetState);
                    $afterRelatedState = $this->buildRelatedSnapshot($instance);
                    $this->upsertRelatedRollbackLogIfNeeded(
                        $instance,
                        $beforeRelatedState,
                        $afterRelatedState,
                        $batchId,
                        app(ChangeLogContext::class)->meta(),
                    );

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

    private function applyRelatedRollbackState(Model $model, array $targetState): void
    {
        if ($model instanceof User && array_key_exists("roles", $targetState)) {
            $this->syncUserRolesFromSnapshot($model, $targetState["roles"]);
        }
    }

    private function syncUserRolesFromSnapshot(User $user, mixed $rolesSnapshot): void
    {
        if (!is_array($rolesSnapshot)) {
            return;
        }

        $roleCodes = array_values(
            array_unique(
                array_filter(
                    array_map(fn($code) => is_string($code) ? trim($code) : "", $rolesSnapshot),
                ),
            ),
        );

        if (empty($roleCodes)) {
            $user->roles()->sync([]);
            return;
        }

        $roles = Role::query()
            ->whereIn("code", $roleCodes)
            ->get(["id", "code"]);
        if ($roles->count() !== count($roleCodes)) {
            throw new RuntimeException(
                "Cannot rollback user roles: one or more role codes not found.",
            );
        }

        $user->roles()->sync($roles->pluck("id")->all());
    }

    private function buildRelatedSnapshot(Model $model): array
    {
        if (!$model instanceof User || !$model->exists) {
            return [];
        }

        $fresh = $model->fresh()?->load(["roles:id,code"]) ?? $model->load(["roles:id,code"]);
        $roles = $fresh->roles->pluck("code")->filter()->values()->all();
        sort($roles);

        return [
            "roles" => $roles,
        ];
    }

    private function upsertRelatedRollbackLogIfNeeded(
        Model $model,
        array $beforeRelatedState,
        array $afterRelatedState,
        string $batchId,
        array $contextMeta,
    ): void {
        if (!$model instanceof User) {
            return;
        }

        $changedFields = [];
        foreach (
            array_unique(
                array_merge(array_keys($beforeRelatedState), array_keys($afterRelatedState)),
            )
            as $key
        ) {
            if (($beforeRelatedState[$key] ?? null) !== ($afterRelatedState[$key] ?? null)) {
                $changedFields[] = $key;
            }
        }

        if (empty($changedFields)) {
            return;
        }

        $beforePayload = [];
        $afterPayload = [];
        foreach ($changedFields as $field) {
            $beforePayload[$field] = $beforeRelatedState[$field] ?? null;
            $afterPayload[$field] = $afterRelatedState[$field] ?? null;
        }

        $entry = ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", (string) $model->id)
            ->where("event", "update")
            ->where("batch_id", $batchId)
            ->latest("created_at")
            ->first();

        if ($entry) {
            $existingBefore = is_array($entry->before) ? $entry->before : [];
            $existingAfter = is_array($entry->after) ? $entry->after : [];
            $existingChanged = is_array($entry->changed_fields) ? $entry->changed_fields : [];
            $existingMeta = is_array($entry->meta) ? $entry->meta : [];

            $entry->before = [...$existingBefore, ...$beforePayload];
            $entry->after = [...$existingAfter, ...$afterPayload];
            $entry->changed_fields = array_values(
                array_unique([...$existingChanged, ...$changedFields]),
            );
            $entry->meta = [
                ...$existingMeta,
                ...$contextMeta,
                "schema_signature" => $model->changeLogSchemaSignature(),
            ];
            $entry->rolled_back_from_id =
                $entry->rolled_back_from_id ?? ($contextMeta["rolled_back_from_id"] ?? null);
            $entry->save();
            return;
        }

        $actor = auth()->user();
        $lastVersion = (int) ChangeLog::query()
            ->where("auditable_type", User::class)
            ->where("auditable_id", (string) $model->id)
            ->max("version");

        ChangeLog::query()->create([
            "auditable_type" => User::class,
            "auditable_id" => (string) $model->id,
            "event" => "update",
            "version" => $lastVersion + 1,
            "before" => $beforePayload,
            "after" => $afterPayload,
            "changed_fields" => array_values($changedFields),
            "actor_type" => $actor ? $actor::class : null,
            "actor_id" => $actor?->getKey(),
            "batch_id" => $batchId,
            "rolled_back_from_id" => $contextMeta["rolled_back_from_id"] ?? null,
            "meta" => [...$contextMeta, "schema_signature" => $model->changeLogSchemaSignature()],
        ]);
    }
}
