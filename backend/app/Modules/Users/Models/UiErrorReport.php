<?php

declare(strict_types=1);

namespace Modules\Users\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UiErrorReport extends Model
{
    use HasUuid;

    protected $table = "ui_error_reports";
    protected $fillable = [
        "user_id",
        "status",
        "page_url",
        "route_name",
        "block_id",
        "description",
        "attachments",
        "payload",
        "processed_at",
    ];

    protected $casts = [
        "attachments" => "array",
        "payload" => "array",
        "processed_at" => "datetime",
    ];

    public $incrementing = false;
    protected $keyType = "string";

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }
}
