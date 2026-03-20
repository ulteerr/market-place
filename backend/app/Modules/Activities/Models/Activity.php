<?php

declare(strict_types=1);

namespace Modules\Activities\Models;

use App\Shared\Traits\HasActionLog;
use App\Shared\Traits\HasChangeLog;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use Modules\Activities\Database\Factories\ActivityFactory;
use Modules\Categories\Models\Category;
use Modules\Files\Models\File;
use Modules\Files\Models\FileReference;
use Modules\Files\Traits\HasFiles;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;

final class Activity extends Model
{
    use HasFactory;
    use HasActionLog;
    use HasChangeLog;
    use HasUuid;
    use HasFiles;

    public const FILE_COLLECTION_COVER = "cover";
    public const FILE_COLLECTION_GALLERY = "gallery";

    protected $table = "activities";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = [
        "organization_id",
        "location_id",
        "name",
        "slug",
        "short_description",
        "description",
        "min_age",
        "max_age",
        "capacity",
        "price_from",
        "price_to",
        "currency",
        "status",
        "is_featured",
        "published_at",
    ];

    protected $casts = [
        "min_age" => "integer",
        "max_age" => "integer",
        "capacity" => "integer",
        "price_from" => "decimal:2",
        "price_to" => "decimal:2",
        "is_featured" => "boolean",
        "published_at" => "datetime",
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, "organization_id");
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(OrganizationLocation::class, "location_id");
    }

    public function primaryCategory(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            "activity_categories",
            "activity_id",
            "category_id",
        );
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ActivitySchedule::class, "activity_id")
            ->orderBy("day_of_week")
            ->orderBy("start_time")
            ->orderBy("end_time");
    }

    public function cover(): MorphOne
    {
        return $this->fileFromCollection(self::FILE_COLLECTION_COVER);
    }

    public function gallery(): MorphMany
    {
        return $this->files()->where("collection", self::FILE_COLLECTION_GALLERY);
    }

    /**
     * @return array<int, string>
     */
    public static function singleFileCollections(): array
    {
        return [self::FILE_COLLECTION_COVER];
    }

    /**
     * @return array<int, string>
     */
    public static function multipleFileCollections(): array
    {
        return [self::FILE_COLLECTION_GALLERY];
    }

    /**
     * @return array<int, string>
     */
    public static function supportedFileCollections(): array
    {
        return array_values(
            array_merge(self::singleFileCollections(), self::multipleFileCollections()),
        );
    }

    public function orderedGalleryFiles(): Collection
    {
        $gallery = $this->relationLoaded("gallery") ? $this->gallery : $this->gallery()->get();

        $references = FileReference::query()
            ->where("owner_type", $this->resolveLiveOwnerType(self::FILE_COLLECTION_GALLERY))
            ->where("owner_id", (string) $this->getKey())
            ->get()
            ->keyBy("file_id");

        return $gallery
            ->sortBy(function (File $file) use ($references): int {
                $reference = $references->get((string) $file->getKey());

                return (int) data_get($reference?->meta, "order", PHP_INT_MAX);
            })
            ->values();
    }

    protected static function newFactory(): ActivityFactory
    {
        return ActivityFactory::new();
    }

    private function resolveLiveOwnerType(string $collection): string
    {
        return sprintf("live:%s:%s", $this->getMorphClass(), $collection);
    }
}
