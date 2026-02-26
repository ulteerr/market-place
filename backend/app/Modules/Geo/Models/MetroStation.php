<?php

declare(strict_types=1);

namespace Modules\Geo\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MetroStation extends Model
{
    use HasUuid;

    protected $table = "metro_stations";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = [
        "name",
        "external_id",
        "line_id",
        "geo_lat",
        "geo_lon",
        "is_closed",
        "metro_line_id",
        "city_id",
        "source",
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, "city_id");
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(MetroLine::class, "metro_line_id");
    }
}
