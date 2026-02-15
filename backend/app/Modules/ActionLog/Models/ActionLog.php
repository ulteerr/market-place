<?php

declare(strict_types=1);

namespace Modules\ActionLog\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Users\Models\User;

final class ActionLog extends Model
{
    use HasUuid;

    protected $table = "model_action_logs";

    public $timestamps = false;

    protected $fillable = [
        "user_id",
        "event",
        "model_type",
        "model_id",
        "ip_address",
        "before",
        "after",
        "changed_fields",
    ];

    protected $casts = [
        "before" => "array",
        "after" => "array",
        "changed_fields" => "array",
        "created_at" => "datetime",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }
}
