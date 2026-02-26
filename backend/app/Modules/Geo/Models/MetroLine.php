<?php

declare(strict_types=1);

namespace Modules\Geo\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MetroLine extends Model
{
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
}
