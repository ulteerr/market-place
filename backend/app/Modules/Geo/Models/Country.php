<?php

declare(strict_types=1);

namespace Modules\Geo\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Geo\Database\Factories\CountryFactory;
use Modules\Organizations\Models\OrganizationLocation;

final class Country extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = "countries";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = ["name", "iso_code"];

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class, "country_id");
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, "country_id");
    }

    public function organizationLocations(): HasMany
    {
        return $this->hasMany(OrganizationLocation::class, "country_id");
    }

    protected static function newFactory(): CountryFactory
    {
        return CountryFactory::new();
    }
}
