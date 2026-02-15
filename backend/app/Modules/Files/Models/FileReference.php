<?php

declare(strict_types=1);

namespace Modules\Files\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FileReference extends Model
{
    use HasUuid;

    protected $table = "file_references";
    protected $keyType = "string";
    public $incrementing = false;

    protected $fillable = ["file_id", "owner_type", "owner_id", "meta"];

    protected $casts = [
        "meta" => "array",
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, "file_id");
    }
}
