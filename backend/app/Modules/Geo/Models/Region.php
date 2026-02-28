<?php

declare(strict_types=1);

namespace Modules\Geo\Models;

use App\Shared\Traits\HasActionLog;
use App\Shared\Traits\HasChangeLog;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Geo\Database\Factories\RegionFactory;
use Modules\Organizations\Models\OrganizationLocation;

final class Region extends Model
{
    use HasFactory;
    use HasUuid;
    use HasActionLog;
    use HasChangeLog;

    protected $table = "regions";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = ["name", "country_id"];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, "country_id");
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, "region_id");
    }

    public function organizationLocations(): HasMany
    {
        return $this->hasMany(OrganizationLocation::class, "region_id");
    }

    protected static function newFactory(): RegionFactory
    {
        return RegionFactory::new();
    }
}
