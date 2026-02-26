<?php

declare(strict_types=1);

namespace Modules\Geo\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Geo\Database\Factories\CityFactory;
use Modules\Organizations\Models\OrganizationLocation;

final class City extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = "cities";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = ["name", "region_id", "country_id"];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, "region_id");
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, "country_id");
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class, "city_id");
    }

    public function organizationLocations(): HasMany
    {
        return $this->hasMany(OrganizationLocation::class, "city_id");
    }

    public function metroStations(): HasMany
    {
        return $this->hasMany(MetroStation::class, "city_id");
    }

    public function metroLines(): HasMany
    {
        return $this->hasMany(MetroLine::class, "city_id");
    }

    protected static function newFactory(): CityFactory
    {
        return CityFactory::new();
    }
}
