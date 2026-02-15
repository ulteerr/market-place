<?php

declare(strict_types=1);

namespace Modules\Users\Models;

use App\Shared\Traits\HasChangeLog;
use App\Shared\Traits\HasUuid;
use App\Shared\Traits\HasActionLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Role extends Model
{
    use HasFactory, HasUuid, HasChangeLog, HasActionLog;
    protected $table = "roles";

    protected $fillable = ["id", "code", "label", "is_system"];

    protected $casts = [
        "is_system" => "boolean",
    ];
    public $incrementing = false;
    protected $keyType = "string";

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, "role_user", "role_id", "user_id");
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            AccessPermission::class,
            "role_access_permission",
            "role_id",
            "permission_id",
        );
    }
}
