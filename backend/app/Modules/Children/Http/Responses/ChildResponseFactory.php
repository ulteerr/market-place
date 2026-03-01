<?php

declare(strict_types=1);

namespace Modules\Children\Http\Responses;

use App\Shared\Http\Responses\StatusResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Children\Http\Resources\ChildResource;
use Modules\Children\Models\Child;

final class ChildResponseFactory
{
    public static function success(Child $child, int $status = 200): JsonResponse
    {
        $child->loadMissing(["user:id,first_name,last_name,middle_name,email"]);

        return StatusResponseFactory::success(new ChildResource($child), $status);
    }

    public static function paginated(
        LengthAwarePaginator $children,
        int $status = 200,
    ): JsonResponse {
        $collection = $children->getCollection();
        if (method_exists($collection, "loadMissing")) {
            $collection->loadMissing(["user:id,first_name,last_name,middle_name,email"]);
        }

        return StatusResponseFactory::paginated(
            $children,
            ChildResource::collection($collection)->resolve(),
            $status,
        );
    }

    public static function successWithMessage(
        string $message,
        Child $child,
        int $status = 200,
    ): JsonResponse {
        $child->loadMissing(["user:id,first_name,last_name,middle_name,email"]);

        return StatusResponseFactory::successWithMessage(
            $message,
            (new ChildResource($child))->resolve(),
            $status,
        );
    }
}
