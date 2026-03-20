<?php

declare(strict_types=1);

namespace Modules\Activities\Models;

use App\Shared\Traits\HasActionLog;
use App\Shared\Traits\HasChangeLog;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Activities\Database\Factories\LeadFactory;
use Modules\Children\Models\Child;
use Modules\Users\Models\User;

final class Lead extends Model
{
    use HasFactory;
    use HasActionLog;
    use HasChangeLog;
    use HasUuid;

    public const REQUEST_FOR_SELF = "self";
    public const REQUEST_FOR_CHILD = "child";

    public const STATUS_NEW = "new";
    public const STATUS_IN_PROGRESS = "in_progress";
    public const STATUS_CONTACTED = "contacted";
    public const STATUS_REGISTERED = "registered";
    public const STATUS_CANCELLED = "cancelled";

    protected $table = "leads";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = [
        "activity_id",
        "user_id",
        "child_id",
        "request_for_type",
        "contact_channels",
        "contact_payload",
        "message",
        "status",
    ];

    protected $casts = [
        "contact_channels" => "array",
        "contact_payload" => "array",
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, "activity_id");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, "child_id");
    }

    protected static function newFactory(): LeadFactory
    {
        return LeadFactory::new();
    }
}
