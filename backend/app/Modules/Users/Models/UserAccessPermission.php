<?php

declare(strict_types=1);

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserAccessPermission extends Model
{
    protected $table = "user_access_permissions";

    protected $fillable = ["user_id", "permission_id", "allowed"];

    protected $casts = [
        "allowed" => "boolean",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(AccessPermission::class, "permission_id");
    }
}
