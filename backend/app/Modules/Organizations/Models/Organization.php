<?php

declare(strict_types=1);

namespace Modules\Organizations\Models;

use App\Shared\Traits\HasActionLog;
use App\Shared\Traits\HasChangeLog;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Organizations\Database\Factories\OrganizationFactory;
use Modules\Users\Models\User;

final class Organization extends Model
{
    use HasFactory;
    use HasUuid;
    use HasActionLog;
    use HasChangeLog;

    protected $table = "organizations";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = [
        "name",
        "description",
        "phone",
        "email",
        "status",
        "source_type",
        "ownership_status",
        "user_id",
        "owner_user_id",
        "created_by_user_id",
        "claimed_at",
    ];

    protected $casts = [
        "claimed_at" => "datetime",
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, "owner_user_id");
    }

    public function legacyOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "created_by_user_id");
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class, "organization_id");
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            "organization_users",
            "organization_id",
            "user_id",
        )->withPivot(["id", "role_id", "role_code", "status", "invited_by_user_id", "joined_at"]);
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(OrganizationJoinRequest::class, "organization_id");
    }

    public function locations(): HasMany
    {
        return $this->hasMany(OrganizationLocation::class, "organization_id");
    }

    protected static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }
}
