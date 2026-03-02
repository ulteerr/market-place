<?php

declare(strict_types=1);

namespace Modules\Organizations\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Metro\Models\MetroStation;

final class OrganizationLocationMetroStation extends Model
{
    use HasUuid;

    protected $table = "organization_location_metro_stations";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = [
        "organization_location_id",
        "metro_station_id",
        "travel_mode",
        "duration_minutes",
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(OrganizationLocation::class, "organization_location_id");
    }

    public function metroStation(): BelongsTo
    {
        return $this->belongsTo(MetroStation::class, "metro_station_id");
    }
}
