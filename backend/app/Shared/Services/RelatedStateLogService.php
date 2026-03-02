<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\ActionLog\Models\ActionLog;
use Modules\ActionLog\Services\ActionLogService;
use Modules\ChangeLog\Models\ChangeLog;
use Modules\ChangeLog\Services\ChangeLogContext;

final class RelatedStateLogService
{
    public function __construct(
        private readonly ActionLogService $actionLogService,
        private readonly ChangeLogContext $changeLogContext,
    ) {}

    /**
     * @param array<string, mixed> $beforeSnapshot
     * @param array<string, mixed> $afterSnapshot
     * @return array<int, string>
     */
    public function resolveChangedFields(array $beforeSnapshot, array $afterSnapshot): array
    {
        $changedFields = [];

        foreach (array_keys($beforeSnapshot) as $key) {
            if (($beforeSnapshot[$key] ?? null) !== ($afterSnapshot[$key] ?? null)) {
                $changedFields[] = $key;
            }
        }

        return $changedFields;
    }

    /**
     * @param array<string, mixed> $beforeSnapshot
     * @param array<string, mixed> $afterSnapshot
     * @param array<int, string> $changedFields
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    public function buildChangedPayload(
        array $beforeSnapshot,
        array $afterSnapshot,
        array $changedFields,
    ): array {
        $beforePayload = [];
        $afterPayload = [];

        foreach ($changedFields as $field) {
            $beforePayload[$field] = $beforeSnapshot[$field] ?? null;
            $afterPayload[$field] = $afterSnapshot[$field] ?? null;
        }

        return [
            "before" => $beforePayload,
            "after" => $afterPayload,
        ];
    }

    /**
     * @param array<string, mixed> $relatedSnapshot
     * @param array<string, mixed>|null $mediaAfter
     */
    public function enrichCreateChangeLogWithRelatedState(
        Model $model,
        array $relatedSnapshot,
        ?array $mediaAfter = null,
    ): void {
        $createLog = ChangeLog::query()
            ->where("auditable_type", $model::class)
            ->where("auditable_id", (string) $model->getKey())
            ->where("event", "create")
            ->latest("created_at")
            ->first();

        if (!$createLog) {
            return;
        }

        $after = is_array($createLog->after) ? $createLog->after : [];
        $createLog->after = [...$after, ...$relatedSnapshot];
        if ($mediaAfter !== null) {
            $createLog->media_after = $mediaAfter === [] ? null : $mediaAfter;
        }
        $createLog->save();
    }

    /**
     * @param array<string, mixed> $beforeSnapshot
     * @param array<string, mixed> $afterSnapshot
     * @param array{
     *   before_media_snapshot?: array<string, mixed>,
     *   after_media_snapshot?: array<string, mixed>,
     *   media_field_map?: array<string, string>,
     *   merge_meta?: callable(array<string, mixed>, array<string, mixed>): array<string, mixed>,
     *   create_meta?: callable(array<string, mixed>): array<string, mixed>
     * } $options
     */
    public function writeRelatedChangeLogIfChanged(
        Model $model,
        array $beforeSnapshot,
        array $afterSnapshot,
        array $options = [],
    ): void {
        $changedFields = $this->resolveChangedFields($beforeSnapshot, $afterSnapshot);
        if ($changedFields === []) {
            return;
        }

        if (
            $this->changeLogContext->disabled() ||
            !method_exists($model, "shouldWriteChangeLog") ||
            !$model->shouldWriteChangeLog("update")
        ) {
            return;
        }

        ["before" => $beforePayload, "after" => $afterPayload] = $this->buildChangedPayload(
            $beforeSnapshot,
            $afterSnapshot,
            $changedFields,
        );

        $beforeMediaSnapshot = (array) ($options["before_media_snapshot"] ?? []);
        $afterMediaSnapshot = (array) ($options["after_media_snapshot"] ?? []);
        $mediaFieldMap = (array) ($options["media_field_map"] ?? []);
        $resolvedMedia = $this->resolveChangedMedia(
            $beforeSnapshot,
            $afterSnapshot,
            $beforeMediaSnapshot,
            $afterMediaSnapshot,
            $mediaFieldMap,
        );

        $batchId = $this->changeLogContext->currentBatchId();
        $entry = ChangeLog::query()
            ->where("auditable_type", $model::class)
            ->where("auditable_id", (string) $model->getKey())
            ->where("event", "update")
            ->where("batch_id", $batchId)
            ->latest("created_at")
            ->first();

        if ($entry) {
            $existingBefore = is_array($entry->before) ? $entry->before : [];
            $existingAfter = is_array($entry->after) ? $entry->after : [];
            $existingChangedFields = is_array($entry->changed_fields) ? $entry->changed_fields : [];
            $existingMeta = is_array($entry->meta) ? $entry->meta : [];
            $existingMediaBefore = is_array($entry->media_before) ? $entry->media_before : [];
            $existingMediaAfter = is_array($entry->media_after) ? $entry->media_after : [];

            $mergeMeta = $options["merge_meta"] ?? null;
            if (is_callable($mergeMeta)) {
                $nextMeta = $mergeMeta($existingMeta, $this->changeLogContext->meta());
            } else {
                $nextMeta = [
                    ...$existingMeta,
                    ...$this->changeLogContext->meta(),
                    "schema_signature" => method_exists($model, "changeLogSchemaSignature")
                        ? $model->changeLogSchemaSignature()
                        : null,
                ];
            }

            $entry->before = [...$existingBefore, ...$beforePayload];
            $entry->after = [...$existingAfter, ...$afterPayload];
            $entry->media_before = [...$existingMediaBefore, ...$resolvedMedia["before"]];
            $entry->media_after = [...$existingMediaAfter, ...$resolvedMedia["after"]];
            $entry->changed_fields = array_values(
                array_unique([...$existingChangedFields, ...$changedFields]),
            );
            $entry->meta = $nextMeta;
            $entry->rolled_back_from_id =
                $entry->rolled_back_from_id ??
                ($this->changeLogContext->meta()["rolled_back_from_id"] ?? null);
            $entry->save();
            return;
        }

        $lastVersion = (int) ChangeLog::query()
            ->where("auditable_type", $model::class)
            ->where("auditable_id", (string) $model->getKey())
            ->max("version");

        $actor = auth()->user();
        $createMeta = $options["create_meta"] ?? null;
        if (is_callable($createMeta)) {
            $meta = $createMeta($this->changeLogContext->meta());
        } else {
            $meta = [
                ...$this->changeLogContext->meta(),
                "schema_signature" => method_exists($model, "changeLogSchemaSignature")
                    ? $model->changeLogSchemaSignature()
                    : null,
            ];
        }

        ChangeLog::query()->create([
            "auditable_type" => $model::class,
            "auditable_id" => (string) $model->getKey(),
            "event" => "update",
            "version" => $lastVersion + 1,
            "before" => $beforePayload,
            "after" => $afterPayload,
            "media_before" => $resolvedMedia["before"] === [] ? null : $resolvedMedia["before"],
            "media_after" => $resolvedMedia["after"] === [] ? null : $resolvedMedia["after"],
            "changed_fields" => array_values($changedFields),
            "actor_type" => $actor ? $actor::class : null,
            "actor_id" => $actor?->getKey(),
            "batch_id" => $batchId,
            "rolled_back_from_id" => $this->changeLogContext->meta()["rolled_back_from_id"] ?? null,
            "meta" => $meta,
        ]);
    }

    /**
     * @param array<string, mixed> $relatedSnapshot
     */
    public function enrichCreateActionLogWithRelatedState(
        Model $model,
        array $relatedSnapshot,
    ): void {
        if (
            !method_exists($model, "shouldWriteActionLog") ||
            !$model->shouldWriteActionLog("create")
        ) {
            return;
        }

        $createLog = ActionLog::query()
            ->where("model_type", $model::class)
            ->where("model_id", (string) $model->getKey())
            ->where("event", "create")
            ->latest("created_at")
            ->first();

        if (!$createLog) {
            return;
        }

        $after = is_array($createLog->after) ? $createLog->after : [];
        $createLog->after = [...$after, ...$relatedSnapshot];
        $createLog->save();
    }

    /**
     * @param array<string, mixed> $beforeSnapshot
     * @param array<string, mixed> $afterSnapshot
     */
    public function writeRelatedActionLogIfChanged(
        Model $model,
        array $beforeSnapshot,
        array $afterSnapshot,
    ): void {
        if (
            !method_exists($model, "shouldWriteActionLog") ||
            !$model->shouldWriteActionLog("update")
        ) {
            return;
        }

        $changedFields = $this->resolveChangedFields($beforeSnapshot, $afterSnapshot);
        if ($changedFields === []) {
            return;
        }

        ["before" => $beforePayload, "after" => $afterPayload] = $this->buildChangedPayload(
            $beforeSnapshot,
            $afterSnapshot,
            $changedFields,
        );

        $actorId = auth()->id();
        $ipAddress = request()?->ip();
        $entry = ActionLog::query()
            ->where("model_type", $model::class)
            ->where("model_id", (string) $model->getKey())
            ->where("event", "update")
            ->where("user_id", $actorId)
            ->where("ip_address", $ipAddress)
            ->where("created_at", ">=", now()->subSeconds(10))
            ->latest("created_at")
            ->first();

        if ($entry) {
            $existingBefore = is_array($entry->before) ? $entry->before : [];
            $existingAfter = is_array($entry->after) ? $entry->after : [];
            $existingChangedFields = is_array($entry->changed_fields) ? $entry->changed_fields : [];

            $entry->before = [...$existingBefore, ...$beforePayload];
            $entry->after = [...$existingAfter, ...$afterPayload];
            $entry->changed_fields = array_values(
                array_unique([...$existingChangedFields, ...$changedFields]),
            );
            $entry->save();
            return;
        }

        $this->actionLogService->logModelEvent(
            $model,
            "update",
            $beforePayload,
            $afterPayload,
            $changedFields,
        );
    }

    /**
     * @param array<string, mixed> $beforeSnapshot
     * @param array<string, mixed> $afterSnapshot
     * @param array<string, mixed> $beforeMediaSnapshot
     * @param array<string, mixed> $afterMediaSnapshot
     * @param array<string, string> $mediaFieldMap
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    private function resolveChangedMedia(
        array $beforeSnapshot,
        array $afterSnapshot,
        array $beforeMediaSnapshot,
        array $afterMediaSnapshot,
        array $mediaFieldMap,
    ): array {
        if ($mediaFieldMap === []) {
            return ["before" => [], "after" => []];
        }

        $mediaBefore = [];
        $mediaAfter = [];
        foreach ($mediaFieldMap as $snapshotField => $mediaKey) {
            if (
                ($beforeSnapshot[$snapshotField] ?? null) ===
                ($afterSnapshot[$snapshotField] ?? null)
            ) {
                continue;
            }

            $mediaBefore[$mediaKey] = $beforeMediaSnapshot[$mediaKey] ?? null;
            $mediaAfter[$mediaKey] = $afterMediaSnapshot[$mediaKey] ?? null;
        }

        return [
            "before" => $mediaBefore,
            "after" => $mediaAfter,
        ];
    }
}
