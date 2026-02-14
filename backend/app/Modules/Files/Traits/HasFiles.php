<?php

declare(strict_types=1);

namespace Modules\Files\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Files\Models\File;

trait HasFiles
{
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, "fileable");
    }

    public function fileFromCollection(string $collection): MorphOne
    {
        return $this->morphOne(File::class, "fileable")->where("collection", $collection);
    }
}
