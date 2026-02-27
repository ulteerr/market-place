<?php

declare(strict_types=1);

namespace Modules\Metro\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Geo\Models\City;
use Modules\Metro\Database\Factories\MetroLineFactory;

final class MetroLine extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = "metro_lines";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = ["name", "external_id", "line_id", "color", "city_id", "source"];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, "city_id");
    }

    public function stations(): HasMany
    {
        return $this->hasMany(MetroStation::class, "metro_line_id");
    }

    protected static function newFactory(): MetroLineFactory
    {
        return MetroLineFactory::new();
    }
}
