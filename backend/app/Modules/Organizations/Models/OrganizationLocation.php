<?php

declare(strict_types=1);

namespace Modules\Organizations\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Country;
use Modules\Geo\Models\District;
use Modules\Geo\Models\Region;
use Modules\Organizations\Database\Factories\OrganizationLocationFactory;

final class OrganizationLocation extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = "organization_locations";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = [
        "organization_id",
        "country_id",
        "region_id",
        "city_id",
        "district_id",
        "address",
        "lat",
        "lng",
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, "organization_id");
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, "country_id");
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, "region_id");
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, "city_id");
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, "district_id");
    }

    protected static function newFactory(): OrganizationLocationFactory
    {
        return OrganizationLocationFactory::new();
    }
}
