<?php

declare(strict_types=1);

namespace Modules\Users\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AccessPermission extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = "access_permissions";

    protected $fillable = ["id", "code", "scope", "label"];

    public $incrementing = false;

    protected $keyType = "string";

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            "role_access_permission",
            "permission_id",
            "role_id",
        );
    }

    public function userOverrides(): HasMany
    {
        return $this->hasMany(UserAccessPermission::class, "permission_id");
    }
}
