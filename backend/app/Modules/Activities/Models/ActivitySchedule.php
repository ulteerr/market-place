<?php

declare(strict_types=1);

namespace Modules\Activities\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Activities\Database\Factories\ActivityScheduleFactory;

final class ActivitySchedule extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = "activity_schedules";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = ["activity_id", "day_of_week", "start_time", "end_time"];

    protected $casts = [
        "day_of_week" => "integer",
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, "activity_id");
    }

    protected static function newFactory(): ActivityScheduleFactory
    {
        return ActivityScheduleFactory::new();
    }
}
