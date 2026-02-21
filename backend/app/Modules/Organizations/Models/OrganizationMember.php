<?php

declare(strict_types=1);

namespace Modules\Organizations\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organizations\Database\Factories\OrganizationMemberFactory;
use Modules\Users\Models\User;

final class OrganizationMember extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = "organization_users";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = [
        "organization_id",
        "user_id",
        "role_id",
        "role_code",
        "status",
        "invited_by_user_id",
        "joined_at",
    ];

    protected $casts = [
        "joined_at" => "datetime",
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, "organization_id");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "invited_by_user_id");
    }

    protected static function newFactory(): OrganizationMemberFactory
    {
        return OrganizationMemberFactory::new();
    }
}
