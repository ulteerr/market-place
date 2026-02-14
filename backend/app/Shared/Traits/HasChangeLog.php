<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use Modules\ChangeLog\Observers\ChangeLogObserver;

trait HasChangeLog
{
    protected static function bootHasChangeLog(): void
    {
        static::observe(ChangeLogObserver::class);
    }

    public function shouldWriteChangeLog(string $event): bool
    {
        return true;
    }

    public function changeLogExcludedAttributes(): array
    {
        return [];
    }

    public function changeLogRollbackAttributes(): array
    {
        return $this->getFillable();
    }

    public function changeLogSchemaSignature(): string
    {
        return hash(
            "sha256",
            json_encode(
                [
                    "table" => $this->getTable(),
                    "fillable" => array_values($this->getFillable()),
                    "casts" => array_keys($this->getCasts()),
                ],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ) ?:
            "",
        );
    }
}
