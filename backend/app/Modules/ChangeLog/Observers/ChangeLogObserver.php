<?php

declare(strict_types=1);

namespace Modules\ChangeLog\Observers;

use Illuminate\Database\Eloquent\Model;
use Modules\ChangeLog\Models\ChangeLog;
use Modules\ChangeLog\Services\ChangeLogContext;

final class ChangeLogObserver
{
    /** @var array<int, array<string, mixed>> */
    private static array $beforeSnapshots = [];

    public function creating(Model $model): void
    {
        $this->rememberBefore($model, []);
    }

    public function updating(Model $model): void
    {
        $before = $this->normalizeAttributes($model, $this->extractOriginalAttributes($model));
        $this->rememberBefore($model, $before);
    }

    public function deleting(Model $model): void
    {
        $before = $this->normalizeAttributes($model, $model->getOriginal());
        $this->rememberBefore($model, $before);
    }

    public function created(Model $model): void
    {
        $this->write(
            $model,
            "create",
            null,
            $this->normalizeAttributes($model, $model->getAttributes()),
        );
        $this->forget($model);
    }

    public function updated(Model $model): void
    {
        $before = self::$beforeSnapshots[spl_object_id($model)] ?? [];
        $after = $this->normalizeAttributes($model, $model->getAttributes());
        $fields = $this->diffFields($before, $after);

        $this->write($model, "update", $before, $after, $fields);
        $this->forget($model);
    }

    public function deleted(Model $model): void
    {
        $before = self::$beforeSnapshots[spl_object_id($model)] ?? [];
        $this->write($model, "delete", $before, null, array_keys($before));
        $this->forget($model);
    }

    public function restored(Model $model): void
    {
        $this->write(
            $model,
            "restore",
            null,
            $this->normalizeAttributes($model, $model->getAttributes()),
        );
        $this->forget($model);
    }

    private function write(
        Model $model,
        string $event,
        ?array $before,
        ?array $after,
        ?array $changedFields = null,
    ): void {
        if (
            !method_exists($model, "shouldWriteChangeLog") ||
            !$model->shouldWriteChangeLog($event)
        ) {
            return;
        }

        $context = app(ChangeLogContext::class);
        if ($context->disabled()) {
            return;
        }

        $actor = auth()->user();
        if ($event === "create" && !$actor) {
            return;
        }

        $auditableType = $model::class;
        $auditableId = (string) $model->getKey();

        $version = ChangeLog::query()
            ->where("auditable_type", $auditableType)
            ->where("auditable_id", $auditableId)
            ->max("version");

        $resolvedFields =
            $event === "create"
                ? null
                : $changedFields ?? $this->diffFields($before ?? [], $after ?? []);

        if ($event === "update" && empty($resolvedFields)) {
            return;
        }

        ChangeLog::query()->create([
            "auditable_type" => $auditableType,
            "auditable_id" => $auditableId,
            "event" => $event,
            "version" => ((int) $version) + 1,
            "before" => $before,
            "after" => $after,
            "changed_fields" => $resolvedFields !== null ? array_values($resolvedFields) : null,
            "actor_type" => $actor ? $actor::class : null,
            "actor_id" => $actor?->getKey(),
            "batch_id" => $context->currentBatchId(),
            "rolled_back_from_id" => $context->meta()["rolled_back_from_id"] ?? null,
            "meta" => [
                ...$context->meta(),
                "schema_signature" => method_exists($model, "changeLogSchemaSignature")
                    ? $model->changeLogSchemaSignature()
                    : null,
            ],
        ]);
    }

    private function rememberBefore(Model $model, array $before): void
    {
        self::$beforeSnapshots[spl_object_id($model)] = $before;
    }

    private function forget(Model $model): void
    {
        $key = spl_object_id($model);
        unset(self::$beforeSnapshots[$key]);
    }

    private function extractOriginalAttributes(Model $model): array
    {
        $original = [];
        foreach (array_keys($model->getAttributes()) as $key) {
            $original[$key] = $model->getOriginal($key);
        }

        return $original;
    }

    private function normalizeAttributes(Model $model, array $attributes): array
    {
        $globalExcludes = config("changelog.exclude", []);
        $modelExcludes = method_exists($model, "changeLogExcludedAttributes")
            ? $model->changeLogExcludedAttributes()
            : [];

        $exclude = array_fill_keys(array_merge($globalExcludes, $modelExcludes), true);
        $normalized = [];

        foreach ($attributes as $key => $value) {
            if (isset($exclude[$key])) {
                continue;
            }

            $normalized[$key] = $this->normalizeValue($value);
        }

        return $normalized;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = $this->decodeJsonString($value);
            if ($decoded !== null) {
                return $this->normalizeValue($decoded);
            }

            return $value;
        }

        if (is_array($value)) {
            if ($this->isAssocArray($value)) {
                ksort($value);
            }

            return array_map(fn($item) => $this->normalizeValue($item), $value);
        }

        return (string) $value;
    }

    private function decodeJsonString(string $value): mixed
    {
        $trimmed = trim($value);
        if (!str_starts_with($trimmed, "{") && !str_starts_with($trimmed, "[")) {
            return null;
        }

        try {
            return json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    private function isAssocArray(array $value): bool
    {
        return !array_is_list($value);
    }

    private function diffFields(array $before, array $after): array
    {
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $changed = [];

        foreach ($keys as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                $changed[] = $key;
            }
        }

        return $changed;
    }
}
