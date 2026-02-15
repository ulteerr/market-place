<?php

declare(strict_types=1);

namespace Modules\ActionLog\Observers;

use Illuminate\Database\Eloquent\Model;
use Modules\ActionLog\Services\ActionLogService;

final class ActionLogObserver
{
    /** @var array<int, array<string, mixed>> */
    private static array $beforeSnapshots = [];

    public function creating(Model $model): void
    {
        $this->rememberBefore($model, []);
    }

    public function updating(Model $model): void
    {
        $before = $this->normalizeAttributes($model->getOriginal());
        $this->rememberBefore($model, $before);
    }

    public function deleting(Model $model): void
    {
        $before = $this->normalizeAttributes($model->getOriginal());
        $this->rememberBefore($model, $before);
    }

    public function created(Model $model): void
    {
        $this->write(
            $model,
            "create",
            null,
            $this->normalizeAttributes($model->getAttributes()),
            null,
        );
        $this->forget($model);
    }

    public function updated(Model $model): void
    {
        $before = self::$beforeSnapshots[spl_object_id($model)] ?? [];
        $after = $this->normalizeAttributes($model->getAttributes());
        $changedFields = $this->diffFields($model, $before, $after);

        if ($changedFields === []) {
            $this->forget($model);
            return;
        }

        $this->write($model, "update", $before, $after, $changedFields);
        $this->forget($model);
    }

    public function deleted(Model $model): void
    {
        $before = self::$beforeSnapshots[spl_object_id($model)] ?? [];
        $this->write($model, "delete", $before, null, array_keys($before));
        $this->forget($model);
    }

    private function write(
        Model $model,
        string $event,
        ?array $before,
        ?array $after,
        ?array $changedFields,
    ): void {
        if (
            method_exists($model, "shouldWriteActionLog") &&
            !$model->shouldWriteActionLog($event)
        ) {
            return;
        }

        if (!(bool) config("action-log.enabled", true)) {
            return;
        }

        $allowedModels = array_values((array) config("action-log.models", []));
        if (!in_array($model::class, $allowedModels, true)) {
            return;
        }

        app(ActionLogService::class)->logModelEvent(
            $model,
            $event,
            $before,
            $after,
            $changedFields,
        );
    }

    private function rememberBefore(Model $model, array $before): void
    {
        self::$beforeSnapshots[spl_object_id($model)] = $before;
    }

    private function forget(Model $model): void
    {
        unset(self::$beforeSnapshots[spl_object_id($model)]);
    }

    private function normalizeAttributes(array $attributes): array
    {
        $normalized = [];
        $exclude = array_fill_keys($this->excludedAttributes(), true);

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
            if (!array_is_list($value)) {
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

    private function diffFields(Model $model, array $before, array $after): array
    {
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $ignored = array_fill_keys($this->excludedAttributes($model), true);
        $changed = [];

        foreach ($keys as $key) {
            if (isset($ignored[$key])) {
                continue;
            }
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                $changed[] = $key;
            }
        }

        return $changed;
    }

    /**
     * @return array<int, string>
     */
    private function excludedAttributes(?Model $model = null): array
    {
        $excluded = (array) config("action-log.exclude", ["created_at", "updated_at"]);

        if ($model && method_exists($model, "actionLogExcludedAttributes")) {
            $extra = $model->actionLogExcludedAttributes();
            if (is_array($extra)) {
                $excluded = [...$excluded, ...$extra];
            }
        }

        return array_values(array_unique(array_filter($excluded, "is_string")));
    }
}
