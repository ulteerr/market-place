<?php

declare(strict_types=1);

namespace Modules\Categories\Models;

use App\Shared\Traits\HasActionLog;
use App\Shared\Traits\HasChangeLog;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Activities\Models\Activity;
use Modules\Categories\Database\Factories\CategoryFactory;

final class Category extends Model
{
    use HasFactory;
    use HasUuid;
    use HasActionLog;
    use HasChangeLog;

    protected $table = "categories";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = ["name", "slug", "parent_id", "sort_order", "is_active"];

    protected $casts = [
        "sort_order" => "integer",
        "is_active" => "boolean",
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, "parent_id");
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, "parent_id")->orderBy("sort_order")->orderBy("name");
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(
            Activity::class,
            "activity_categories",
            "category_id",
            "activity_id",
        );
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
