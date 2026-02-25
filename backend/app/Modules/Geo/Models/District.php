<?php

declare(strict_types=1);

namespace Modules\Geo\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Geo\Database\Factories\DistrictFactory;
use Modules\Organizations\Models\OrganizationLocation;

final class District extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = "districts";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = ["name", "city_id"];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, "city_id");
    }

    public function organizationLocations(): HasMany
    {
        return $this->hasMany(OrganizationLocation::class, "district_id");
    }

    protected static function newFactory(): DistrictFactory
    {
        return DistrictFactory::new();
    }
}
