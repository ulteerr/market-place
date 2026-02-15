<?php

declare(strict_types=1);

namespace Modules\Files\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

final class File extends Model
{
    use HasUuid;

    protected $table = "files";
    protected $keyType = "string";
    public $incrementing = false;

    protected $fillable = [
        "disk",
        "path",
        "original_name",
        "mime_type",
        "size",
        "collection",
        "fileable_type",
        "fileable_id",
    ];

    protected $appends = ["url"];

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function references(): HasMany
    {
        return $this->hasMany(FileReference::class, "file_id");
    }

    public function getUrlAttribute(): string
    {
        $url = Storage::disk($this->disk)->url($this->path);

        if (str_starts_with($url, "http://") || str_starts_with($url, "https://")) {
            return $url;
        }

        return url($url);
    }
}
