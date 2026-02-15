<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use Modules\ActionLog\Observers\ActionLogObserver;

trait HasActionLog
{
    protected static function bootHasActionLog(): void
    {
        static::observe(ActionLogObserver::class);
    }

    public function shouldWriteActionLog(string $event): bool
    {
        return true;
    }
}
