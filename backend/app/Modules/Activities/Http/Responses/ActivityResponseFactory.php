<?php

declare(strict_types=1);

namespace Modules\Activities\Http\Responses;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Modules\Activities\Http\Resources\ActivityFeedItemResource;
use Modules\Activities\Http\Resources\ActivityResource;
use Modules\Activities\Models\Activity;

final class ActivityResponseFactory
{
    public static function success(Activity $activity, int $status = 200): JsonResponse
    {
        $activity->loadMissing(self::relations());

        return StatusResponseFactory::success(new ActivityResource($activity), $status);
    }

    public static function collection(Collection $activities, int $status = 200): JsonResponse
    {
        if (method_exists($activities, "loadMissing")) {
            $activities->loadMissing(self::relations());
        }

        return StatusResponseFactory::success(
            ActivityResource::collection($activities)->resolve(),
            $status,
        );
    }

    public static function feedCollection(Collection $activities, int $status = 200): JsonResponse
    {
        if (method_exists($activities, "loadMissing")) {
            $activities->loadMissing(self::feedRelations());
        }

        return StatusResponseFactory::success(
            ActivityFeedItemResource::collection($activities)->resolve(),
            $status,
        );
    }

    public static function feed(
        Collection $activities,
        ?string $nextCursor,
        int $status = 200,
    ): JsonResponse {
        if (method_exists($activities, "loadMissing")) {
            $activities->loadMissing(self::feedRelations());
        }

        return StatusResponseFactory::success(
            [
                "items" => ActivityFeedItemResource::collection($activities)->resolve(),
                "next_cursor" => $nextCursor,
            ],
            $status,
        );
    }

    public static function paginated(
        LengthAwarePaginator $activities,
        int $status = 200,
    ): JsonResponse {
        $collection = $activities->getCollection();
        if (method_exists($collection, "loadMissing")) {
            $collection->loadMissing(self::relations());
        }

        return StatusResponseFactory::paginated(
            $activities,
            ActivityResource::collection($collection)->resolve(),
            $status,
        );
    }

    public static function successWithMessage(
        string $message,
        Activity $activity,
        int $status = 200,
    ): JsonResponse {
        $activity->loadMissing(self::relations());

        return StatusResponseFactory::successWithMessage(
            $message,
            (new ActivityResource($activity))->resolve(),
            $status,
        );
    }

    /**
     * @return array<int, string>
     */
    private static function relations(): array
    {
        return [
            "organization:id,name",
            "location:id,organization_id,city_id,address",
            "location.city:id,name",
            "primaryCategory:id,name,slug,parent_id,sort_order,is_active",
            "primaryCategory.parent:id,name,slug,parent_id,sort_order,is_active",
            "schedules",
            "cover:id,fileable_id,fileable_type,disk,path,original_name,mime_type,size,collection",
            "gallery:id,fileable_id,fileable_type,disk,path,original_name,mime_type,size,collection",
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function feedRelations(): array
    {
        return [
            "organization:id,name",
            "location:id,city_id,address",
            "location.city:id,name",
            "primaryCategory:id,name,slug,parent_id",
            "primaryCategory.parent:id,name,slug",
            "cover:id,fileable_id,fileable_type,disk,path,original_name,mime_type,size,collection",
        ];
    }
}
