<?php

declare(strict_types=1);

namespace Modules\Metro\Models;

use App\Shared\Traits\HasActionLog;
use App\Shared\Traits\HasChangeLog;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Geo\Models\City;
use Modules\Metro\Database\Factories\MetroStationFactory;

final class MetroStation extends Model
{
    use HasFactory;
    use HasUuid;
    use HasActionLog;
    use HasChangeLog;

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

    protected static function newFactory(): MetroStationFactory
    {
        return MetroStationFactory::new();
    }
}
