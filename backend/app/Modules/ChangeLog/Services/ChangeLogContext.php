<?php

declare(strict_types=1);

namespace Modules\ChangeLog\Services;

use Illuminate\Support\Str;

final class ChangeLogContext
{
    private bool $disabled = false;
    private ?string $batchId = null;
    private array $meta = [];

    public function disable(): void
    {
        $this->disabled = true;
    }

    public function enable(): void
    {
        $this->disabled = false;
    }

    public function disabled(): bool
    {
        return $this->disabled;
    }

    public function withDisabledLogging(callable $callback): mixed
    {
        $previous = $this->disabled;
        $this->disabled = true;

        try {
            return $callback();
        } finally {
            $this->disabled = $previous;
        }
    }

    public function withMeta(array $meta, callable $callback): mixed
    {
        $previous = $this->meta;
        $this->meta = [...$this->meta, ...$meta];

        try {
            return $callback();
        } finally {
            $this->meta = $previous;
        }
    }

    public function meta(): array
    {
        return $this->meta;
    }

    public function currentBatchId(): string
    {
        if ($this->batchId !== null) {
            return $this->batchId;
        }

        $fromHeader = request()?->header("X-ChangeLog-Batch");
        if (is_string($fromHeader) && trim($fromHeader) !== "") {
            $this->batchId = trim($fromHeader);
            return $this->batchId;
        }

        $this->batchId = (string) Str::uuid();

        return $this->batchId;
    }
}
