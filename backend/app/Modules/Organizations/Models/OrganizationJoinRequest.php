<?php

declare(strict_types=1);

namespace Modules\Organizations\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organizations\Database\Factories\OrganizationJoinRequestFactory;
use Modules\Users\Models\User;

final class OrganizationJoinRequest extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = "organization_join_requests";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = [
        "organization_id",
        "user_id",
        "status",
        "message",
        "review_note",
        "reviewed_by_user_id",
        "reviewed_at",
    ];

    protected $casts = [
        "reviewed_at" => "datetime",
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, "organization_id");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "reviewed_by_user_id");
    }

    protected static function newFactory(): OrganizationJoinRequestFactory
    {
        return OrganizationJoinRequestFactory::new();
    }
}
