<?php

declare(strict_types=1);

namespace Modules\ChangeLog\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;

final class ChangeLog extends Model
{
    use HasUuid;

    protected $table = "change_logs";
    protected $keyType = "string";
    public $incrementing = false;

    protected $fillable = [
        "auditable_type",
        "auditable_id",
        "event",
        "version",
        "before",
        "after",
        "changed_fields",
        "actor_type",
        "actor_id",
        "batch_id",
        "rolled_back_from_id",
        "meta",
    ];

    protected $casts = [
        "before" => "array",
        "after" => "array",
        "changed_fields" => "array",
        "meta" => "array",
    ];

    public function actor(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, "actor_type", "actor_id");
    }
}
